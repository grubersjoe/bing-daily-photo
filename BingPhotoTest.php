<?php

use PHPUnit\Framework\TestCase;

require('BingPhoto.php');

class BingPhotoTest extends TestCase
{
    /**
     * @dataProvider invalidArgumentProvider
     * @param $expected
     * @param $args
     * @throws Exception
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
     * @throws Exception
     */
    public function testCount($args = [])
    {
        $bingPhoto = new BingPhoto($args);
        $count = min($args['n'] ?? 1, BingPhoto::LIMIT_N);
        $this->assertCount($count, $bingPhoto->getImages());
    }

    /**
     * @dataProvider qualityProvider
     * @param $args
     * @throws Exception
     */
    public function testResolution($args = [])
    {
        $bingPhoto = new BingPhoto($args);
        foreach ($bingPhoto->getImages() as $image) {
            list($width, $height) = getimagesize($image['url']);
            $this->assertEquals($width . 'x' . $height, $args['quality'] ?? BingPhoto::QUALITY_HIGH);
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
            'unavailable quality' => [
                ['quality' => '1920x1080'],
                ['quality' => '800x600'],
            ],
            'invalid quality' => [
                ['quality' => '1920x1080'],
                ['quality' => '😳'],
            ],
            'empty quality' => [
                ['quality' => '1920x1080'],
                ['quality' => null],
            ],
        ];
    }

    public function countProvider()
    {
        return [
            'one image' => [
                []
            ],
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

    public function qualityProvider()
    {
        return [
            'no arguments' => [
                []
            ],
            'low quality' => [
                ['quality' => BingPhoto::QUALITY_LOW]
            ],
            'high quality' => [
                ['quality' => BingPhoto::QUALITY_HIGH]
            ]
        ];
    }

    // TODO: write tests for caching feature
}
