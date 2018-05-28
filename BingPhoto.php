<?php

/**
 * A simple class which fetches Bing's image of the day with meta data
 */
class BingPhoto
{

    // Constants
    const TOMORROW = -1;
    const TODAY = 0;
    const YESTERDAY = 1;
    const LIMIT_N = 8; // Bing's API returns at most 8 images
    const QUALITY_LOW = '1366x768';
    const QUALITY_HIGH = '1920x1080';

    const RUNFILE_NAME = '.lastrun';

    // API
    const BASE_URL = 'https://www.bing.com';
    const JSON_URL = '/HPImageArchive.aspx?format=js';

    private $args;
    private $images = [];
    private $cachedImages = [];

    /**
     * Constructor: Fetches image(s) of the day from Bing
     * @param array $args Options array
     *      $args['n'] int Number of images / days
     *      $args['date'] intDate offset. 0 equals today, 1 = yesterday, and so on.
     *      $args['locale'] string Localization string (en-US, de-DE, ...)
     *      $args['quality'] string Resolution of images(s)
     *      $args['cacheDir'] string Cache (download) images in this directory
     * @throws Exception
     */
    public function __construct(array $args = [])
    {
        $this->setArgs($args);
        $this->fetchImagesMetadata();

        // Caching
        $cacheDir = $this->args['cacheDir'];
        if (!empty($cacheDir)) {
            if (file_exists($cacheDir)) {
                $this->cacheImages();
            } else {
                throw new Exception(sprintf('Given cache directory %s does not exist', $cacheDir));
            }
        }
    }

    /**
     * Returns the first fetched image
     * @return array The image array with its URL and further meta data
     */
    public function getImage()
    {
        $images = $this->getImages(1);

        return $images[0];
    }

    /**
     * Returns n fetched images
     * @param int $n Number of images to return
     * @return array Image data
     */
    public function getImages($n = 1)
    {
        $n = max($n, count($this->images));

        return array_slice($this->images, 0, $n);
    }

    /**
     * Returns the list of locally cached images
     * @return array List of absolute paths to cached images
     */
    public function getCachedImages()
    {
        return $this->cachedImages;
    }

    /**
     * Returns the class arguments
     * @return array Class arguments
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Sets the class arguments
     * @param array $args
     */
    private function setArgs(array $args)
    {
        $defaultArgs = [
            'n' => 1,
            'locale' => str_replace('_', '-', Locale::getDefault()),
            'date' => self::TODAY,
            'quality' => self::QUALITY_HIGH,
            'cacheDir' => false,
        ];
        $args = array_replace($defaultArgs, $args);
        $this->args = $this->sanitizeArgs($args);
    }

    /**
     * Performs sanity checks
     * @param array $args Arguments
     * @return array Sanitized arguments
     */
    private function sanitizeArgs(array $args)
    {
        $args['date'] = max($args['date'], self::TOMORROW);
        $args['n'] = min(max($args['n'], 1), self::LIMIT_N);
        if (!in_array($args['quality'], [self::QUALITY_HIGH, self::QUALITY_LOW])) {
            $args['quality'] = self::QUALITY_HIGH;
        }

        return $args;
    }

    /**
     * Fetches the image meta data from Bing (JSON)
     * @throws Exception
     */
    private function fetchImagesMetadata()
    {
        $url = $this->buildApiUrl($this->args['date'], $this->args['n'], $this->args['locale']);
        $data = json_decode(file_get_contents($url), true);
        $error = json_last_error();

        if ($error === JSON_ERROR_NONE && is_array($data['images'])) {
            $this->images = $data['images'];
            $this->setAbsoluteUrl();
            $this->setQuality();
        } else {
            throw new Exception('Unable to retrieve JSON data: ' . $error);
        }
    }

    /**
     * Caches the images on local disk
     */
    private function cacheImages()
    {
        $prevArgs = $this->readRunfile();
        $fetchList = [];

        // Build a list of to be cached dates
        $baseDate = (new DateTime())->modify(sprintf('-%d day', $this->args['date'] - 1));
        for ($i = 0; $i < $this->args['n']; $i++) {
            $date = $baseDate->modify('-1 day')->format('Ymd');
            $fetchList[$date] = true;
        }

        // Check current cache
        $dirIterator = new DirectoryIterator($this->args['cacheDir']);
        foreach ($dirIterator as $image) {
            if ($image->isFile() && $image->getExtension() === 'jpg') {
                $imageShouldBeCached = in_array($image->getBasename('.jpg'), array_keys($fetchList));
                if ($prevArgs === $this->args && $imageShouldBeCached) {
                    // Image already present - no need to download it again
                    printf('already present - skipping %s' . PHP_EOL, $image->getFilename());
                    unset($fetchList[$image->getBasename('.jpg')]);
                    $this->cachedImages[] = $image->getRealPath();
                } else {
                    // Config changed or cache duration expired - remove the file
                    printf('removing %s' . PHP_EOL, $image->getFilename());
                    unlink($image->getRealPath());
                }
            }
        }

        $this->fetchImageFiles($fetchList);

        if ($prevArgs !== $this->args) {
            $this->writeRunfile();
        }
    }

    /**
     * Downloads images to cache directory
     * @param array $fetchList
     */
    private function fetchImageFiles(array $fetchList)
    {
        try {
            $this->fetchImagesMetadata();
            foreach ($this->images as $image) {
                if (in_array($image['enddate'], array_keys($fetchList))) {
                    $fileName = sprintf('%s/%s.jpg', $this->args['cacheDir'], $image['enddate']);
                    if (file_put_contents($fileName, file_get_contents($image['url']))) {
                        $this->cachedImages[] = $fileName;
                    }
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            exit($e->getMessage());
        }
    }

    /**
     * Write current arguments to runfile
     */
    private function writeRunfile()
    {
        $argsJson = json_encode($this->args);
        $filename = sprintf('%s/%s', $this->args['cacheDir'], self::RUNFILE_NAME);
        file_put_contents($filename, $argsJson);
    }

    /**
     * Returns the persisted arguments in the runfile
     * @return array|null
     */
    private function readRunfile()
    {
        $filename = sprintf('%s/%s', $this->args['cacheDir'], self::RUNFILE_NAME);

        if (file_exists($filename)) {
            $runfile = json_decode(file_get_contents($filename), true);
            if (JSON_ERROR_NONE === json_last_error()) {
                return $runfile;
            } else {
                unlink($filename);
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Build the API URL
     * @param int $date The date offset
     * @param int $n Number of images to fetch
     * @param int $locale Locale code
     * @return string The URL to the JSON endpoint
     */
    private function buildApiUrl($date, $n, $locale)
    {
        return sprintf(self::BASE_URL . self::JSON_URL . '&idx=%d&n=%d&mkt=%s', $date, $n, $locale);
    }

    /**
     * Changes relative to absolute URLs
     */
    private function setAbsoluteUrl()
    {
        foreach ($this->images as $key => $image) {
            $this->images[$key]['url'] = self::BASE_URL . $image['url'];
        }
    }


    /**
     * Sets the image quality
     */
    private function setQuality()
    {
        foreach ($this->images as $key => $image) {
            $url = str_replace(self::QUALITY_HIGH, $this->args['quality'], $image['url']);
            $this->images[$key]['url'] = $url;
        }
    }
}
