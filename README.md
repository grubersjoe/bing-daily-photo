# Bing Daily Photo

![CI](https://github.com/grubersjoe/bing-daily-photo/actions/workflows/test.yml/badge.svg)

BingPhoto is a simple PHP class to fetch Bing's image of the day with meta data.

It is also possible to cache the images locally, which can be useful in combination with a periodic cronjob. See the `cacheDir` parameter for this (optional) feature. Disclaimer: this might be a copyright issue.

## Installation

Use [Composer](https://getcomposer.org/) to install this package:

```sh
composer require grubersjoe/bing-daily-photo
```

## Basic usage

```php
<?php
$bing = new grubersjoe\BingPhoto();
$image = $bing->getImage();

// Example result ($image)
[
    [startdate] => '20160913'
    [fullstartdate] => '201609130700'
    [enddate] => '20160914'
    [url] => 'http://www.bing.com/az/hprichbg/rb/Meteora_EN-US6763889417_1920x1080.jpg'
    [urlbase] => '/az/hprichbg/rb/Meteora_EN-US6763889417'
    [copyright] => 'Roussanou and other monasteries in Metéora, Greece (© Stian Rekdal/Nimia)'   
    // ...
]
```

## Parameters / options

| Parameter     | Description                                          | Default                            | Valid values                                                                                                |
|---------------|------------------------------------------------------|------------------------------------|-------------------------------------------------------------------------------------------------------------|
| `cacheDir`    | Directory for image caching                          | `null`                             | An existing directory, otherwise the directory will be created if possible                                  |
| `date`        | Date of photo                                        | `BingPhoto::DATE_TODAY`            | `BingPhoto::DATE_YESTERDAY`<br>`BingPhoto::DATE_TODAY`<br>`BingPhoto::DATE_TOMORROW`<br>`any integer >= -1` |
| `locale`      | Locale code                                          | `Locale::getDefault()`             | Whatever language Bing supports                                                                             |
| `n`           | Number of photos to fetch, going from date backwards | 1                                  | 1 - 8                                                                                                       |
| `orientation` | Image orientation                                    | `BingPhoto::ORIENTATION_LANDSCAPE` | `BingPhoto::ORIENTATION_LANDSCAPE`, `BingPhoto::ORIENTATION_PORTRAIT`                                       |
| `quality`     | Image resolution                                     | `BingPhoto::QUALITY_HIGH`          | `BingPhoto::QUALITY_LOW`<br>`BingPhoto::QUALITY_HIGH`                                                       |


## Examples

```php
// Fetches two images of the day starting yesterday from Bing
$bing = new grubersjoe\BingPhoto([
    'n' => 2,
    'date' => grubersjoe\BingPhoto::YESTERDAY
]);

foreach ($bing->getImages() as $image) {
    printf('<img src="%s">', $image['url']);
}
```

```php
// Fetches the current image of the day in low resolution from the French Bing portal
$bing = new grubersjoe\BingPhoto([
    'locale' => 'fr-FR',
    'quality' => grubersjoe\BingPhoto::QUALITY_LOW,
]);

printf('<img src="%s">', $bing->getImage()['url']);
```

```php
// Fetches three images of the day in high quality from the German Bing portal, starting yesterday
$bing = new grubersjoe\BingPhoto([
    'n' => 3,
    'date' => grubersjoe\BingPhoto::YESTERDAY,
    'locale' => 'de-DE',
    'quality' => grubersjoe\BingPhoto::QUALITY_HIGH,
]);

foreach ($bing->getImages() as $image) {
    printf('<img src="%s">', $image['url']);
}
```

```php
// Fetches the current image of the day in portrait orientation
$bing = new grubersjoe\BingPhoto([
    'orientation' => grubersjoe\BingPhoto::ORIENTATION_PORTRAIT
]);
```

```php
// Using the local cache 
$bing = new grubersjoe\BingPhoto([
    'cacheDir' => '/tmp/bing-photo',
    'n' => 5,
    'quality' => grubersjoe\BingPhoto::QUALITY_LOW,
]);
```
