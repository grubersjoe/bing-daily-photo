<?php

/**
 * A simple class, which fetches Bing's image of the day with meta data
 */
class BingPhoto {
    // Configuration 'enumeration'
    const DATE_TOMORROW   = -1;
    const DATE_TODAY      =  0;
    const DATE_YESTERDAY  =  1;
    const LIMIT_N         =  8; // Bing's API returns at most 8 images
    const RESOLUTION_LOW  = '1366x768';
    const RESOLUTION_HIGH = '1920x1080';

    // API
    const BASE_URL = 'http://www.bing.com';
    const JSON_URL = '/HPImageArchive.aspx?format=js';

    private $args;
    private $data;

    /**
     * Constructor: Fetches image(s) of the day from Bing
     * @param int    $date        Date offset. 0 equals today, 1 = yesterday, etc.
     * @param int    $n           Number of images / days
     * @param string $locale      Localization
     * @param string $resolution  Resolution of images(s)
     */
    public function __construct($date = self::DATE_TODAY, $n = 1, $locale = 'en-US', $resolution = self::RESOLUTION_HIGH) {
        $this->args = array(
            'date'       => $date,
            'n'          => $n,
            'locale'     => $locale,
            'resolution' => $resolution
        );
        $this->sanityCheck();

        // Constructing API url
        $url = self::BASE_URL . self::JSON_URL
            . '&idx=' . $this->args['date']
            . '&n='   . $this->args['n']
            . '&mkt'  . $this->args['locale'];
        try {
            $this->data = $this->fetchJSON($url);
            $this->data = $this->setQualityAndBaseUrl($this->data['images']);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Returns exactly one fetched image
     * @return [type] [description]
     */
    public function getImage() {
        $image = $this->getImages(1);
        return $image[0];
    }

    /**
     * Returns n fetched images
     * @param  int $limit  Number of images to return
     * @return array       Image data
     */
    public function getImages($n = 1) {
        $max = count($this->data);
        if ($n > $max)
            $n = $max;
        return array_slice($this->data, 0, $n);
    }

    /**
     * Performs some sanity checks
     * @param  array $args Arguments of constructor
     * @return array       Valid arguments or false on failure
     */
    private function sanityCheck() {
        if ($this->args['date'] < self::DATE_TOMORROW)
            $this->args['date'] = self::DATE_TOMORROW;

        if ($this->args['n'] > self::LIMIT_N)
            $this->args['n'] = self::LIMIT_N;
        if ($this->args['n'] < 1)
            $this->args['n'] = 1;

        if (false === in_array($this->args['resolution'], array(self::RESOLUTION_LOW, self::RESOLUTION_HIGH)))
            $this->args['resolution'] = self::RESOLUTION_HIGH;
    }

    /**
     * Fetches an associative array from given JSON url
     * @param  string $url  JSON URL
     * @return array        Associative data array
     * @throws Exception
     */
    private function fetchJSON($url) {
        $data  = json_decode(file_get_contents($url), true);
        $error = json_last_error();
        if ($data !== null && $error === JSON_ERROR_NONE) {
            return $data;
        } else {
            throw new Exception('Unable to retrieve JSON data: ' . $error);
        }
    }

    /**
     * Prepends base url and replaces resolution of images in url
     * @param array $images  Array with image data
     * @return array         Modified image data array
     */
    private function setQualityAndBaseUrl($images) {
        foreach ($images as $i => $image) {
            $images[$i]['url'] = self::BASE_URL . str_replace(self::RESOLUTION_LOW, $this->args['resolution'], $image['url']);
        }
        return $images;
    }
}