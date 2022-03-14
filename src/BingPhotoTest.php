<?php


namespace grubersjoe;

use PHPUnit\Framework\TestCase;
use Exception;

class BingPhotoTest extends TestCase
{
    public const CACHE_DIR = './.cache/';

    /**
     * @dataProvider invalidArgumentProvider
     *
     * @param $expected
     * @param array $args
     *
     * @throws Exception
     */
    public function testArgsValidation($expected, array $args = []): void
    {
        $bingPhoto = new BingPhoto($args);
        $actual = $bingPhoto->getArgs();

        foreach ($expected as $key => $expectedArg) {
            static::assertEquals($expectedArg, $actual[$key]);
        }
    }

    /**
     * @dataProvider countArgsProvider
     *
     * @param array $args
     *
     * @throws Exception
     */
    public function testCount(array $args = [])
    {
        $bingPhoto = new BingPhoto($args);
        $count = min($args['n'] ?? 1, BingPhoto::LIMIT_N);
        static::assertCount($count, $bingPhoto->getImages());
    }

    // TODO: locale test
    // TODO: date test

    /**
     * @dataProvider qualityArgsProvider
     *
     * @param array $args
     *
     * @throws Exception
     */
    public function testQuality(array $args = []): void
    {
        $bingPhoto = new BingPhoto($args);
        foreach ($bingPhoto->getImages() as $image) {
            list($width, $height) = getimagesize($image['url']);
            $quality = $args['quality'] ?? BingPhoto::QUALITY_HIGH;
            static::assertEquals($width . 'x' . $height, $quality);
        }
    }

    /**
     * @dataProvider cacheArgsProvider
     *
     * @param array $args
     *
     * @throws Exception
     */
    public function testCache(array $args = []): void
    {
        if (file_exists($args['cacheDir'])) {
            system(sprintf('rm -r %s', self::CACHE_DIR));
        }

        $bingPhoto = new BingPhoto($args);
        $args = $bingPhoto->getArgs();

        // Check if runfile was created
        static::assertFileExists(sprintf('%s/%s', $args['cacheDir'], BingPhoto::RUNFILE_NAME));

        // Check quantity
        static::assertCount($args['n'], $bingPhoto->getCachedImages());

        $mtimes = [];
        foreach ($bingPhoto->getCachedImages() as $image) {
            static::assertFileExists($image);
            $mtimes[$image] = filemtime($image);
        }

        // Ensure that images are actually cached (with same config)
        $bingPhoto = new BingPhoto($args);
        foreach ($bingPhoto->getCachedImages() as $image) {
            clearstatcache($image);
            $mtime = filemtime($image);
            static::assertEquals($mtime, $mtimes[$image]);
            $mtimes[$image] = $mtime;
        }

        // Check cache busting (changed config)
        sleep(1);
        $args['quality'] = BingPhoto::QUALITY_LOW;
        $bingPhoto = new BingPhoto($args);
        foreach ($bingPhoto->getCachedImages() as $image) {
            clearstatcache($image);
            static::assertNotEquals(filemtime($image), $mtimes[$image]);
        }
    }

    /**
     * @dataProvider invalidCacheArgsProvider
     *
     * @param array $args
     *
     * @throws Exception
     */
    public function testInvalidCache(array $args = []): void
    {
        if (!empty($args['cacheDir']) && !file_exists($args['cacheDir'])) {
            $this->expectException(Exception::class);
        }

        $bingPhoto = new BingPhoto($args);
        static::assertEmpty($bingPhoto->getCachedImages());
    }

    public function invalidArgumentProvider(): array
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

    public function countArgsProvider(): array
    {
        return [
            'one image' => [
                [],
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

    public function qualityArgsProvider(): array
    {
        return [
            'no arguments' => [
                [],
            ],
            'low quality' => [
                ['quality' => BingPhoto::QUALITY_LOW],
            ],
            'high quality' => [
                ['quality' => BingPhoto::QUALITY_HIGH],
            ],
        ];
    }

    public function cacheArgsProvider(): array
    {
        return [
            'default options' => [
                [
                    'cacheDir' => self::CACHE_DIR,
                    'locale' => 'de-DE',
                ],
            ],
            'three images' => [
                [
                    'cacheDir' => self::CACHE_DIR,
                    'locale' => 'de-DE',
                    'n' => 3,
                ],
            ],
        ];
    }

    public function invalidCacheArgsProvider(): array
    {
        return [
            'empty cache directory' => [
                [
                    'cacheDir' => '',
                ],
            ],
            'invalid cache directory' => [
                [
                    'cacheDir' => '/foo',
                ],
            ],
        ];
    }
}
