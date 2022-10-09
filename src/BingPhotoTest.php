<?php

namespace grubersjoe;

use Exception;
use PHPUnit\Framework\TestCase;

class BingPhotoTest extends TestCase
{
    public const CACHE_DIR = './.cache/';

    /**
     * @dataProvider invalidArgumentProvider
     *
     * @param $expected
     * @param array $options
     *
     * @throws Exception
     */
    public function testOptionsValidation($expected, array $options = []): void
    {
        $bingPhoto = new BingPhoto($options);
        $actual = $bingPhoto->getOptions();

        foreach ($expected as $key => $expectedArg) {
            static::assertEquals($expectedArg, $actual[$key]);
        }
    }

    /**
     * @dataProvider countArgsProvider
     *
     * @throws Exception
     */
    public function testCount(array $options = [])
    {
        $bingPhoto = new BingPhoto($options);
        $count = min($options['n'] ?? 1, BingPhoto::LIMIT_N);
        static::assertCount($count, $bingPhoto->getImages());
    }

    // TODO: locale test
    // TODO: date test

    /**
     * @dataProvider qualityOptionProvider
     *
     * @throws Exception
     */
    public function testQuality(array $options = []): void
    {
        $bingPhoto = new BingPhoto($options);
        foreach ($bingPhoto->getImages() as $image) {
            list($width, $height) = getimagesize($image['url']);
            $quality = $options['quality'] ?? BingPhoto::QUALITY_HIGH;
            static::assertEquals($width . 'x' . $height, $quality);
        }
    }

    /**
     * @dataProvider orientationOptionProvider
     *
     * @throws Exception
     */
    public function testOrientation(string $expected, array $options = []): void
    {
        $bingPhoto = new BingPhoto($options);
        static::assertEquals($expected, $bingPhoto->getOptions()['quality']);
        static::assertStringContainsString($expected, $bingPhoto->getImage()['url']);
    }

    /**
     * @dataProvider cacheOptionProvider
     *
     * @throws Exception
     */
    public function testCache(array $options = []): void
    {
        if (file_exists($options['cacheDir'])) {
            system(sprintf('rm -r %s', self::CACHE_DIR));
        }

        $bingPhoto = new BingPhoto($options);
        $options = $bingPhoto->getOptions();

        // Check if runfile was created
        static::assertFileExists(sprintf('%s/%s', $options['cacheDir'], BingPhoto::RUNFILE_NAME));

        // Check quantity
        static::assertCount($options['n'], $bingPhoto->getCachedImages());

        $mtimes = [];
        foreach ($bingPhoto->getCachedImages() as $image) {
            static::assertFileExists($image);
            $mtimes[$image] = filemtime($image);
        }

        // Ensure that images are actually cached (with same config)
        $bingPhoto = new BingPhoto($options);
        foreach ($bingPhoto->getCachedImages() as $image) {
            clearstatcache($image);
            $mtime = filemtime($image);
            static::assertEquals($mtime, $mtimes[$image]);
            $mtimes[$image] = $mtime;
        }

        // Check cache busting (changed config)
        sleep(1);
        $options['quality'] = BingPhoto::QUALITY_LOW;
        $bingPhoto = new BingPhoto($options);
        foreach ($bingPhoto->getCachedImages() as $image) {
            clearstatcache($image);
            static::assertNotEquals(filemtime($image), $mtimes[$image]);
        }
    }

    /**
     * @dataProvider invalidCacheArgsProvider
     *
     * @throws Exception
     */
    public function testInvalidCache(array $options = []): void
    {
        if (!empty($options['cacheDir']) && !file_exists($options['cacheDir'])) {
            $this->expectException(Exception::class);
        }

        $bingPhoto = new BingPhoto($options);
        static::assertEmpty($bingPhoto->getCachedImages());
    }

    public function invalidArgumentProvider(): array
    {
        return [
            'invalid date in future' => [
                ['date' => BingPhoto::TOMORROW],
                ['date' => -2],
            ],
            'n too large' => [
                ['n' => BingPhoto::LIMIT_N],
                ['n' => 9],
            ],
            'n zero' => [
                ['n' => BingPhoto::YESTERDAY],
                ['n' => 0],
            ],
            'n negative' => [
                ['n' => BingPhoto::YESTERDAY],
                ['n' => -2],
            ],
            'unavailable quality' => [
                ['quality' => BingPhoto::QUALITY_HIGH],
                ['quality' => '800x600'],
            ],
            'invalid quality' => [
                ['quality' => BingPhoto::QUALITY_HIGH],
                ['quality' => '😳'],
            ],
            'empty quality' => [
                ['quality' => BingPhoto::QUALITY_HIGH],
                ['quality' => null],
            ],
            'invalid orientation' => [
                ['orientation' => BingPhoto::ORIENTATION_LANDSCAPE],
                ['orientation' => '👀'],
            ],
            'empty orientation' => [
                ['orientation' => BingPhoto::ORIENTATION_LANDSCAPE],
                ['orientation' => null],
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

    public function qualityOptionProvider(): array
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

    public function orientationOptionProvider(): array
    {
        return [
            'landscape' => [
                '1920x1080',
                ['orientation' => BingPhoto::ORIENTATION_LANDSCAPE],
            ],
            'portrait' => [
                '1080x1920',
                ['orientation' => BingPhoto::ORIENTATION_PORTRAIT],
            ],
        ];
    }

    public function cacheOptionProvider(): array
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
        ];
    }
}
