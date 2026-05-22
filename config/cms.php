<?php

return [
    'default_locale' => env('CMS_DEFAULT_LOCALE', 'nl'),
    'default_meta_description' => env('CMS_DEFAULT_META_DESCRIPTION', 'Modern Laravel base for custom websites.'),

    'page_statuses' => [
        'draft',
        'published',
    ],

    'reserved_slugs' => [
        'admin',
        'api',
        'up',
    ],

    'site_extension_paths' => [
        'app' => 'app/Site',
        'views' => 'resources/views/site',
        'scss' => 'resources/scss/site',
        'routes' => 'routes/site.php',
    ],
];
