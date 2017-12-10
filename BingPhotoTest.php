<?php

use PHPUnit\Framework\TestCase;

require('BingPhoto.php');

class BingPhotoTest extends TestCase
{

    /** @var BingPhoto */
    protected $bing;

    protected function setUp()
    {
        $this->bing = new BingPhoto();
    }

    /**
     * @dataProvider argumentProvider
     * @param $args
     */
    public function testCount($args = [])
    {
        $this->bing->setArgs($args);
        $count = isset($args['n']) ? $args['n'] : 1;
        $this->assertCount($count, $this->bing->getImages());
    }

    /**
     * @dataProvider argumentProvider
     * @param $args
     */
    public function testResolution($args = [])
    {
        $this->bing->setArgs($args);
        foreach ($this->bing->getImages() as $image) {
            if (isset($args['resolution'])) {
                list($resX, $resY) = getimagesize($image['url']);
                $this->assertEquals($resX . 'x' . $resY, $args['resolution']);
            } else {
                $this->assertTrue(true);
            }
        }
    }

    public function argumentProvider()
    {
        return [
            'no arguments' => [],
            'low resolution, de-DE' => [
                [
                    'resolution' => BingPhoto::RESOLUTION_LOW,
                    'locale' => 'de-DE'
                ]
            ],
            'high resolution, fr-FR' => [
                [
                    'resolution' => BingPhoto::RESOLUTION_HIGH,
                    'locale' => 'fr-FR'
                ]
            ],
            'three last images' => [
                [
                    'n' => 3
                ]
            ],
            'yesterday\'s image' => [
                [
                    'n' => 1,
                    'date' => BingPhoto::YESTERDAY
                ]
            ],
        ];
    }
}
