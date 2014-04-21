<?php

/**
 * A simple class, which fetches Bing's image of the day with meta data
 */
class BingPhoto {
    const BASE_URL = 'http://www.bing.com';
    const JSON_URL = '/HPImageArchive.aspx?format=js';
    const RESOLUTION_LOW  = '1366x768';
    const RESOLUTION_HIGH = '1920x1080';

    private $resolution;
    private $data;

    /**
     * Constructor: Fetches image(s) of the day from Bing
     * @param int    $date        Date offset. 0 equals today, 1 = yesterday, etc.
     * @param int    $n           Number of images / days
     * @param string $locale      Localization
     * @param string $resolution  Resolution of images(s)
     */
    public function __construct($date = 0, $n = 1, $locale = 'en-US', $resolution = self::RESOLUTION_HIGH) {
        $this->resolution = $resolution;
        // Bing API url
        $url = self::BASE_URL . self::JSON_URL . '&idx=' . $date . '&n=' . $n . '&mkt' . $locale;
        try {
            $this->data = $this->fetchJSON($url);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Returns fetched image data as associative array
     * @param  int $limit  Number of images to return
     * @return array       Image data
     */
    public function getImages($limit = null) {
        $max = count($this->data);
        if ($limit > $max) $limit = $max;
        return array_slice($this->data, 0, $limit);
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
            $data = $this->setQualityAndBaseUrl($data['images']);
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
            $images[$i]['url'] = self::BASE_URL . str_replace(self::RESOLUTION_LOW, $this->resolution, $image['url']);
        }
        return $images;
    }
}