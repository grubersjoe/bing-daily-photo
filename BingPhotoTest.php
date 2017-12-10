<?php
require('BingPhoto.php');

class BingPhotoTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var BingPhoto
     */
    protected $bing;

    protected function setUp()
    {
        $this->bing = new BingPhoto();
    }

    /**
     * @dataProvider invalidArgumentProvider
     * @param $expected
     * @param $args
     */
    public function testArgsValidation($expected, $args = [])
    {
        $this->bing->setArgs($args);
        $actual = $this->bing->getArgs();

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
                list($width, $height) = getimagesize($image['url']);
                $this->assertEquals($width . 'x' . $height, $args['resolution']);
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
