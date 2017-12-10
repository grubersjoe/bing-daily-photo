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
     * @dataProvider argumentProvider
     * @param $args
     */
    public function testCount($args = [])
    {
        $bingPhoto = new BingPhoto($args);
        $count = $args['n'] ?? 1;
        $this->assertCount($count, $bingPhoto->getImages());
    }

    /**
     * @dataProvider argumentProvider
     * @param $args
     */
    public function testResolution($args = [])
    {
        $bingPhoto = new BingPhoto($args);
        foreach ($bingPhoto->getImages() as $image) {
            if (isset($args['resolution'])) {
                list($width, $height) = getimagesize($image['url']);
                $this->assertEquals($width . 'x' . $height, $args['resolution']);
            } else {
                $this->assertTrue(true);
            }
        }
    }

    public function invalidArgumentProvider()
    {
        return [
            'invalid date in future' => [
                [
                    'date' => -1
                ],
                [
                    'date' => -2
                ]
            ],
            'n too large' => [
                [
                    'n' => 8
                ],
                [
                    'n' => 9
                ]
            ],
            'n zero' => [
                [
                    'n' => 1
                ],
                [
                    'n' => 0
                ]
            ],
            'n negative' => [
                [
                    'n' => 1
                ],
                [
                    'n' => -2
                ]
            ],
            'unavailable resolution' => [
                [
                    'resolution' => '1920x1080'
                ],
                [
                    'resolution' => '1920x1200'
                ]
            ],
            'invalid resolution' => [
                [
                    'resolution' => '1920x1080'
                ],
                [
                    'resolution' => 'foo'
                ]
            ],
        ];
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
