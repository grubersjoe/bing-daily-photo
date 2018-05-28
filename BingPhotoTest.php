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
     * @dataProvider countArgsProvider
     * @param $args
     * @throws Exception
     */
    public function testCount($args = [])
    {
        $bingPhoto = new BingPhoto($args);
        $count = min($args['n'] ?? 1, BingPhoto::LIMIT_N);
        $this->assertCount($count, $bingPhoto->getImages());
    }

    // TODO: locale test
    // TODO: date test

    /**
     * @dataProvider qualityArgsProvider
     * @param $args
     * @throws Exception
     */
    public function testQuality($args = [])
    {
        $bingPhoto = new BingPhoto($args);
        foreach ($bingPhoto->getImages() as $image) {
            list($width, $height) = getimagesize($image['url']);
            $this->assertEquals($width . 'x' . $height, $args['quality'] ?? BingPhoto::QUALITY_HIGH);
        }
    }

    /**
     * @dataProvider cacheArgsProvider
     * @param $args
     * @throws Exception
     */
    public function testCache($args = [])
    {
        if (!file_exists($args['cacheDir'])) {
            mkdir($args['cacheDir']);
        }

        $bingPhoto = new BingPhoto($args);
        $args = $bingPhoto->getArgs();

        // Check if runfile was created
        $this->assertFileExists(sprintf('%s/%s', $args['cacheDir'], BingPhoto::RUNFILE_NAME));
        $this->assertCount($args['n'], $bingPhoto->getCachedImages());

        $mtimes = [];
        foreach ($bingPhoto->getCachedImages() as $image) {
            $this->assertFileExists($image);
            $mtimes[$image] = filemtime($image);
        }

        // Ensure that images are actually cached (with same config)
        $bingPhoto = new BingPhoto($args);
        foreach ($bingPhoto->getCachedImages() as $image) {
            clearstatcache($image);
            $mtime = filemtime($image);
            $this->assertEquals($mtime, $mtimes[$image]);
            $mtimes[$image] = $mtime;
        }

        // Check cache busting (changed config)
        sleep(1);
        $args['quality'] = BingPhoto::QUALITY_LOW;
        $bingPhoto = new BingPhoto($args);
        foreach ($bingPhoto->getCachedImages() as $image) {
            clearstatcache($image);
            $this->assertNotEquals(filemtime($image), $mtimes[$image]);
        }
    }

    /**
     * @dataProvider invalidCacheArgsProvider
     * @param $args
     * @throws Exception
     */
    public function testInvalidCache($args = [])
    {
        if (!empty($args['cacheDir']) && !file_exists($args['cacheDir'])) {
            $this->expectException(Exception::class);
        }

        $bingPhoto = new BingPhoto($args);
        $this->assertEmpty($bingPhoto->getCachedImages());

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

    public function countArgsProvider()
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

    public function qualityArgsProvider()
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

    public function cacheArgsProvider()
    {
        return [
            'default options' => [
                [
                    'cacheDir' => '/tmp/bing',
                ]
            ],
            'three images' => [
                [
                    'cacheDir' => '/tmp/bing',
                    'n' => 3
                ]
            ],
        ];
    }

    public function invalidCacheArgsProvider()
    {
        return [
            'empty cache directory' => [
                ['cacheDir' => '']
            ],
            'invalid cache directory' => [
                ['cacheDir' => '/foo']
            ],
        ];
    }
}
