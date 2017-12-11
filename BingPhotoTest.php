<?php

use PHPUnit\Framework\TestCase;

require('BingPhoto.php');

class BingPhotoTest extends TestCase
{
    /**
     * @dataProvider invalidArgumentProvider
     * @param $expected
     * @param $args
     */
    public function testArgsValidation($expected, $args = [])
    {
        $bingPhoto = new BingPhoto($args);
        $actual = $bingPhoto->getArgs();

        foreach ($expected as $key => $expectedArg) {
            $this->assertEquals($expectedArg, $actual[$key]);
        }
    }

    /**
     * @dataProvider countProvider
     * @param $args
     */
    public function testCount($args = [])
    {
        $bingPhoto = new BingPhoto($args);
        $count = min($args['n'] ?? 1, BingPhoto::LIMIT_N);
        $this->assertCount($count, $bingPhoto->getImages());
    }

    /**
     * @dataProvider resolutionProvider
     * @param $args
     */
    public function testResolution($args = [])
    {
        $bingPhoto = new BingPhoto($args);
        foreach ($bingPhoto->getImages() as $image) {
            list($width, $height) = getimagesize($image['url']);
            $this->assertEquals($width . 'x' . $height, $args['resolution'] ?? BingPhoto::RESOLUTION_HIGH);
        }
    }

    public function invalidArgumentProvider()
    {
        return [
            'invalid date in future' => [
                ['date' => -1],
                ['date' => -2],
            ],
            'n too large' => [
                ['n' => 8],
                ['n' => 9],
            ],
            'n zero' => [
                ['n' => 1],
                ['n' => 0],
            ],
            'n negative' => [
                ['n' => 1],
                ['n' => -2],
            ],
            'unavailable resolution' => [
                ['resolution' => '1920x1080'],
                ['resolution' => '800x600'],
            ],
            'invalid resolution' => [
                ['resolution' => '1920x1080'],
                ['resolution' => '😳'],
            ],
            'empty resolution' => [
                ['resolution' => '1920x1080'],
                ['resolution' => null],
            ],
        ];
    }

    public function countProvider()
    {
        return [
            'one image' => [],
            'one image explicitly' => [
                ['n' => 1],
            ],
            'two images' => [
                ['n' => 2],
            ],
            'eight images' => [
                ['n' => 8],
            ],
            'nine images' => [
                ['n' => 9],
            ],
        ];
    }

    public function resolutionProvider()
    {
        return [
            'no arguments' => [],
            'low resolution' => [
                ['resolution' => BingPhoto::RESOLUTION_LOW]
            ],
            'high resolution' => [
                ['resolution' => BingPhoto::RESOLUTION_HIGH]
            ]
        ];
    }
}
