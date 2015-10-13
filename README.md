# Bing Daily Photo

BingPhoto is a simple PHP class which fetches Bing's image of the day with meta data.

## Basic usage

```php
$bing  = new BingPhoto();
$image = $bing->getImage();
```

## Parameters

The class has some optional parameters to control various options:

| Parameter   |Description        |Default              |Valid values|
|-------------|-------------------|---------------------|------------|
| $date|Date of photo|`BingPhoto::DATE_TODAY` |`BingPhoto::DATE_YESTERDAY`, `BingPhoto::DATE_TODAY`, `BingPhoto::DATE_TOMORROW`, `any integer >= -1`|
| $n|Number of photos to fetch, going from date backwards|1|1 - 8|
| $locale     |Locale code|en-US|Whatever language Bing supports|
| $resolution |Image resolution|`BingPhoto::RESOLUTION_HIGH`|`BingPhoto::RESOLUTION_LOW`, `BingPhoto::RESOLUTION_HIGH`|

## Examples

```php
// Fetches two images of the day in high resolution from the American Bing portal
$bing  = new BingPhoto(BingPhoto::YESTERDAY, 2);
$images = $bing->getImages();
```

```php
// Fetches three images of the day in low resolution, starting yesterday from the French Bing portal
$bing  = new BingPhoto(BingPhoto::YESTERDAY, 3, 'fr-FR', BingPhoto::RESOLUTION_LOW);
foreach ($bing->getImages() as $image) {
    printf('<img src="%s">', $image['url']);
}
```
