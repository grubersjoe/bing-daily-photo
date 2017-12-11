# Bing Daily Photo

BingPhoto is a simple PHP class to fetch Bing's image of the day with meta data.

## Basic usage

```php
$bing = new BingPhoto();
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

## Parameters

The class has some optional parameters to control various options:

| Parameter   |Description        |Default              |Valid values|
|-------------|-------------------|---------------------|------------|
| $date|Date of photo|`BingPhoto::DATE_TODAY` |`BingPhoto::DATE_YESTERDAY`, `BingPhoto::DATE_TODAY`, `BingPhoto::DATE_TOMORROW`, `any integer >= -1`|
| $n|Number of photos to fetch, going from date backwards|1|1 - 8|
| $locale     |Locale code|`Locale::getDefault()`|Whatever language Bing supports|
| $resolution |Image resolution|`BingPhoto::RESOLUTION_HIGH`|`BingPhoto::RESOLUTION_LOW`, `BingPhoto::RESOLUTION_HIGH`|

## Examples

```php
// Fetches two images of the day starting yesterday from Bing
$bing = new BingPhoto([
    'n' => 2,
    'date' => BingPhoto::YESTERDAY
]);

foreach ($bing->getImages() as $image) {
    printf('<img src="%s">', $image['url']);
}
```

```php
// Fetches the current image of the day in low resolution from the French Bing portal
$bing = new BingPhoto([
    'locale' => 'fr-FR',
    'resolution' => BingPhoto::RESOLUTION_LOW,
]);

printf('<img src="%s">', $bing->getImage()['url']);
```

```php
// Fetches three images of the day in high resolution from the German Bing portal, starting yesterday
$bing = new BingPhoto([
    'n' => 3,
    'date' => BingPhoto::YESTERDAY,
    'locale' => 'de-DE',
    'resolution' => BingPhoto::RESOLUTION_HIGH,
]);

foreach ($bing->getImages() as $image) {
    printf('<img src="%s">', $image['url']);
}
```
