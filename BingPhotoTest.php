<?php
require('BingPhoto.php');

class BingPhotoTest extends PHPUnit_Framework_TestCase {

    protected $bp;

    protected function setUp() {
        $this->bp = new BingPhoto();
    }

    /**
     * @dataProvider argumentProvider
     * @param $args
     */
    public function testCount($args = array()) {
        $this->bp->setArgs($args);
        $count = isset($args['n']) ? $args['n'] : 1;
        $this->assertCount($count, $this->bp->getImages());
    }

    /**
     * @dataProvider argumentProvider
     * @param $args
     */
    public function testResolution($args = array()) {
        $this->bp->setArgs($args);
        foreach ($this->bp->getImages() as $image) {
            if (isset($args['resolution'])) {
                list($resX, $resY) = getimagesize($image['url']);
                $this->assertEquals($resX . 'x' . $resY, $args['resolution']);
            }
        }
    }

    public function argumentProvider() {
        return array(
            'no arguments' => array(),
            'low resolution, de-DE' => array(
                array (
                    'resolution' => BingPhoto::RESOLUTION_LOW,
                    'locale' => 'de-DE'
                )
            ),
            'high resolution, fr-FR' => array(
                array (
                    'resolution' => BingPhoto::RESOLUTION_HIGH,
                    'locale' => 'fr-FR'
                )
            ),
            'three last images' => array(
                array (
                    'n' => 3
                )
            ),
            'yesterday\'s image' => array(
                array (
                    'n' => 1,
                    'date' => BingPhoto::YESTERDAY
                )
            ),
        );
    }
}
