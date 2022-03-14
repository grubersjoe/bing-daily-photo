<?php

namespace grubersjoe;

use DateTime;
use DirectoryIterator;
use Exception;
use Locale;

/**
 * A simple class which fetches Bing's image of the day with metadata.
 */
class BingPhoto
{
    // Constants
    public const TOMORROW = -1;
    public const TODAY = 0;
    public const YESTERDAY = 1;
    public const LIMIT_N = 8; // Bing's API returns at most 8 images
    public const QUALITY_LOW = '1366x768';
    public const QUALITY_HIGH = '1920x1080';

    public const RUNFILE_NAME = '.lastrun';

    // API
    public const BASE_URL = 'https://www.bing.com';
    public const JSON_URL = '/HPImageArchive.aspx?format=js';

    protected array $args;
    protected array $images = [];
    protected array $cachedImages = [];

    /**
     * Constructor: Fetches image(s) of the day from Bing.
     *
     * @param array $args Options array, see README
     *
     * @throws Exception
     */
    public function __construct(array $args = [])
    {
        $this->setArgs($args);
        $this->fetchImageMetadata();

        // Caching
        $cacheDir = $this->args['cacheDir'];

        if (!empty($cacheDir)) {
            if (file_exists($cacheDir) || @mkdir($cacheDir, 0755)) {
                $this->cacheImages();
            } else {
                throw new Exception(sprintf('Given cache directory %s does not exist or cannot be created', $cacheDir));
            }
        }
    }

    /**
     * Returns the first fetched image.
     *
     * @return array The image array with its URL and further metadata
     */
    public function getImage(): array
    {
        $images = $this->getImages(1);

        return $images[0];
    }

    /**
     * Returns n fetched images.
     *
     * @param int $n Number of images to return
     *
     * @return array Image data
     */
    public function getImages(int $n = 1): array
    {
        $n = max($n, count($this->images));

        return array_slice($this->images, 0, $n);
    }

    /**
     * Returns the list of locally cached images.
     *
     * @return array List of absolute paths to cached images
     */
    public function getCachedImages(): array
    {
        return $this->cachedImages;
    }

    /**
     * Returns the class arguments.
     *
     * @return array Class arguments
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Sets the class arguments.
     */
    protected function setArgs(array $args): void
    {
        $defaultArgs = [
            'cacheDir' => false,
            'date' => self::TODAY,
            'locale' => str_replace('_', '-', Locale::getDefault()),
            'n' => 1,
            'quality' => self::QUALITY_HIGH,
        ];

        $args = array_replace($defaultArgs, $args);
        $this->args = $this->sanitizeArgs($args);
    }

    /**
     * Performs sanity checks.
     *
     * @param array $args Arguments
     *
     * @return array Sanitized arguments
     */
    protected function sanitizeArgs(array $args): array
    {
        $args['date'] = max($args['date'], self::TOMORROW);
        $args['n'] = min(max($args['n'], 1), self::LIMIT_N);

        if (!in_array($args['quality'], [self::QUALITY_HIGH, self::QUALITY_LOW])) {
            $args['quality'] = self::QUALITY_HIGH;
        }

        return $args;
    }

    /**
     * Fetches the image metadata from Bing (JSON).
     *
     * @throws Exception
     */
    protected function fetchImageMetadata(): void
    {
        $url = sprintf(self::BASE_URL . self::JSON_URL . '&idx=%d&n=%d&mkt=%s',
            $this->args['date'], $this->args['n'], $this->args['locale']);

        $data = json_decode(file_get_contents($url), true);
        $error = json_last_error();

        if (JSON_ERROR_NONE === $error && is_array($data['images'])) {
            $this->images = $data['images'];
            $this->setAbsoluteUrl();
            $this->setQuality();
        } else {
            throw new Exception('Unable to retrieve JSON data: ' . $error);
        }
    }

    /**
     * Caches the images on local disk.
     *
     * @throws Exception
     */
    protected function cacheImages(): void
    {
        $prevArgs = $this->readRunfile();
        $fetchList = [];

        // Build a list of to be cached dates
        // Careful: the configured timezone in PHP is crucial here
        $today = new DateTime();
        $baseDate = $today->modify(sprintf('-%d day', $this->args['date'] - 1));

        for ($i = 0; $i < $this->args['n']; $i++) {
            $date = $baseDate->modify('-1 day')->format('Ymd');
            $fetchList[$date] = true;
        }

        // Check current cache
        $dirIterator = new DirectoryIterator($this->args['cacheDir']);
        foreach ($dirIterator as $image) {
            if ($image->isFile() && 'jpg' === $image->getExtension()) {
                $imageShouldBeCached = in_array($image->getBasename('.jpg'), array_keys($fetchList));

                if ($prevArgs === $this->args && $imageShouldBeCached) {
                    // Image already present - no need to download it again
                    unset($fetchList[$image->getBasename('.jpg')]);
                    $this->cachedImages[] = $image->getRealPath();
                } else {
                    // Config changed or cache duration expired - remove the file
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
     * Downloads images to cache directory.
     *
     * @throws Exception
     */
    protected function fetchImageFiles(array $fetchList): void
    {
        $this->fetchImageMetadata();

        foreach ($this->images as $image) {
            if (in_array($image['enddate'], array_keys($fetchList))) {
                $fileName = sprintf('%s/%s.jpg', $this->args['cacheDir'], $image['enddate']);

                if (file_put_contents($fileName, file_get_contents($image['url']))) {
                    $this->cachedImages[] = realpath($fileName);
                }
            }
        }
    }

    /**
     * Write current arguments to runfile.
     */
    protected function writeRunfile(): void
    {
        $argsJson = json_encode($this->args);
        $filename = sprintf('%s/%s', $this->args['cacheDir'], self::RUNFILE_NAME);
        file_put_contents($filename, $argsJson);
    }

    /**
     * Returns the persisted arguments in the runfile.
     */
    protected function readRunfile(): ?array
    {
        $filename = sprintf('%s/%s', $this->args['cacheDir'], self::RUNFILE_NAME);

        if (file_exists($filename)) {
            $runfile = json_decode(file_get_contents($filename), true);
            if (JSON_ERROR_NONE === json_last_error()) {
                return $runfile;
            }
            unlink($filename);
        }

        return null;
    }

    /**
     * Changes relative to absolute URLs.
     */
    protected function setAbsoluteUrl(): void
    {
        foreach ($this->images as $key => $image) {
            $this->images[$key]['url'] = self::BASE_URL . $image['url'];
        }
    }

    /**
     * Sets the image quality.
     */
    protected function setQuality(): void
    {
        foreach ($this->images as $key => $image) {
            $url = str_replace(self::QUALITY_HIGH, $this->args['quality'], $image['url']);
            $this->images[$key]['url'] = $url;
        }
    }
}
