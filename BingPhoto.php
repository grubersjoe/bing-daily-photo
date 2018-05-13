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
    const RESOLUTION_LOW = '1366x768';
    const RESOLUTION_HIGH = '1920x1080';

    // API
    const BASE_URL = 'https://www.bing.com';
    const JSON_URL = '/HPImageArchive.aspx?format=js';

    private $args;
    private $images = null;

    /**
     * Constructor: Fetches image(s) of the day from Bing
     * @param array $args Options array
     *      $args['n'] int $n Number of images / days
     *      $args['date'] int $date Date offset. 0 equals today, 1 = yesterday, and so on.
     *      $args['locale'] string $locale Localization string (en-US, de-DE, ...)
     *      $args['resolution'] string $resolution Resolution of images(s)
     */
    public function __construct(array $args = [])
    {
        $this->setArgs($args);
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
        $defaults = [
            'n' => 1,
            'locale' => str_replace('_', '-', Locale::getDefault()),
            'date' => self::TODAY,
            'resolution' => self::RESOLUTION_HIGH
        ];

        $args = array_replace($defaults, $args);
        $this->args = $this->sanitizeArgs($args);

        try {
            $this->fetchImages();
        } catch (Exception $e) {
            error_log($e->getMessage());
            exit($e->getMessage());
        }
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
        if (!in_array($args['resolution'], [self::RESOLUTION_HIGH, self::RESOLUTION_LOW])) {
            $args['resolution'] = self::RESOLUTION_HIGH;
        }

        return $args;
    }

    /**
     * Fetches the image JSON data from Bing
     * @throws Exception
     */
    private function fetchImages()
    {
        $format = self::BASE_URL . self::JSON_URL . '&idx=%s&n=%s&mkt=%s';
        $url = sprintf($format, $this->args['date'], $this->args['n'], $this->args['locale']);

        $this->images = $this->fetchImagesFromApi($url);
        $this->setQuality();
    }

    /**
     * Fetches an associative array from given JSON URL
     * @param  string $url JSON URL
     * @return array Associative data array
     * @throws Exception
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
            throw new Exception('Unable to retrieve JSON data: ' . $error);
        }

        return $images;
    }

    /**
     * Sets the image resolution
     */
    private function setQuality()
    {
        foreach ($this->images as $key => $image) {
            $url = str_replace(self::RESOLUTION_HIGH, $this->args['resolution'], $image['url']);
            $this->images[$key]['url'] = $url;
        }
    }
}
