# Bing Daily Photo

BingPhoto is a simple PHP class which fetches Bing's image of the day with meta data.

## Usage

```php
$bing  = new BingPhoto();
$image = $bing->getImage();
```

The constructor has some optional parameters:

| Parameter   |Description        |Default              |Valid values|
|-------------|-------------------|---------------------|------------|
| $date|Date of photo|`BingPhoto::DATE_TODAY` |`BingPhoto::DATE_YESTERDAY`, `BingPhoto::DATE_TODAY`, `BingPhoto::DATE_TOMORROW`, `any integer >= -1`|
| $n|Number of photos to fetch, going from date backwards|1|1 - 8|
| $locale     |Locale code|en-US|Whatever language Bing supports|
| $resolution |Image resolution|`BingPhoto::RESOLUTION_HIGH`|`BingPhoto::RESOLUTION_LOW`, `BingPhoto::RESOLUTION_HIGH`|

```php
// Fetches three images of the day in low resolution, starting yesterday
$bing  = new BingPhoto(BingPhoto::YESTERDAY, 3, 'en-US', BingPhoto::RESOLUTION_LOW);
// Use the getImages() methode then
$images = $bing->getImages();
```
