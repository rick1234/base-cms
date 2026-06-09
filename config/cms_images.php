<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Frontend image variants
    |--------------------------------------------------------------------------
    |
    | WebP is the default because it gives a strong compression/browser-support
    | balance for public websites. Individual handles can still request jpg,
    | png, gif, webp, or avif explicitly.
    |
    */
    'default_format' => env('CMS_IMAGE_FORMAT', 'webp'),

    'quality' => [
        'jpg' => (int) env('CMS_IMAGE_JPG_QUALITY', 82),
        'webp' => (int) env('CMS_IMAGE_WEBP_QUALITY', 82),
        'avif' => (int) env('CMS_IMAGE_AVIF_QUALITY', 55),
        'png_indexed' => filter_var(env('CMS_IMAGE_PNG_INDEXED', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'cache_disk' => env('CMS_IMAGE_CACHE_DISK', 'public'),
    'cache_directory' => env('CMS_IMAGE_CACHE_DIRECTORY', 'image-cache'),

    'lightbox' => [
        'width' => (int) env('CMS_IMAGE_LIGHTBOX_WIDTH', 1800),
        'height' => (int) env('CMS_IMAGE_LIGHTBOX_HEIGHT', 1800),
        'crop' => filter_var(env('CMS_IMAGE_LIGHTBOX_CROP', false), FILTER_VALIDATE_BOOLEAN),
    ],
];
