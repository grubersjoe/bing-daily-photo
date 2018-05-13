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

    // API
    const BASE_URL = 'https://www.bing.com';
    const JSON_URL = '/HPImageArchive.aspx?format=js';

    private $args;
    private $images = null;

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

        $cacheDir = $this->args['cacheDir'];
        if (empty($cacheDir)) {
            $this->fetchImages();
        } elseif (file_exists($cacheDir)) {
            $this->cacheImages();
        } else {
            throw new Exception(sprintf('Given cache directory %s does not exist', $cacheDir));
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
     * Fetches the image JSON data from Bing
     */
    private function fetchImages()
    {
        $url = $this->buildApiUrl($this->args['date'], $this->args['n'], $this->args['locale']);
        $this->images = $this->fetchImagesFromApi($url);
        $this->setQuality();
    }

    /**
     * Caches the image
     * @return array
     */
    private function cacheImages()
    {
        // TODO: read runfile if present

        $resultList = [];
        $fetchList = [];

        $baseDate = (new DateTime())->modify(sprintf('-%d day', $this->args['date'] - 1));
        for ($i = 0; $i < $this->args['n']; $i++) {
            $date = $baseDate->modify('-1 day')->format('Ymd');
            $fetchList[$date] = true;
        }

        // 1. check which images are already present
        $dirIterator = new DirectoryIterator($this->args['cacheDir']);
        foreach ($dirIterator as $image) {
            if ($image->isFile() && $image->getExtension() === 'jpg') {
                // TODO: fetch anyway, if config has changed (runfile)
                if (in_array($image->getBasename('.jpg'), array_keys($fetchList))) {
                    // file already present - no need to download it again
                    unset($fetchList[$image->getBasename('.jpg')]);
                    $resultList[] = $image->getRealPath();
                } else {
                    // cache duration expired - remove the file
                    unlink($image->getRealPath());
                }
            }
        }

        // 2. download missing ones
        $this->fetchImages();
        foreach ($this->images as $image) {
            if (in_array($image['enddate'], array_keys($fetchList))) {
                $fileName = sprintf('%s/%s.jpg', $this->args['cacheDir'], $image['enddate']);
                if (file_put_contents($fileName, file_get_contents($image['url']))) {
                    $resultList[] = $this->args['cacheDir'] . '/' . $fileName;
                }
            }
        }

        // TODO: write runfile

        return $resultList;
    }

    /**
     * Fetches an associative array from given JSON URL
     * @param  string $url JSON URL
     * @return array Associative data array
     */
    private function fetchImagesFromApi($url)
    {
        $data = json_decode(file_get_contents($url), true);
        $error = json_last_error();

        if ($error === JSON_ERROR_NONE && is_array($data['images'])) {
            $images = $data['images'];
            foreach ($images as $key => $image) {
                $images[$key]['url'] = self::BASE_URL . $image['url'];
            }
        } else {
            $msg = 'Unable to retrieve JSON data: ' . $error;
            error_log($msg);
            exit($msg);
        }

        return $images;
    }

    private function buildApiUrl($date, $n, $locale)
    {
        return sprintf(self::BASE_URL . self::JSON_URL . '&idx=%d&n=%d&mkt=%s', $date, $n, $locale);
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
