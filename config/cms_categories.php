<?php

use App\Models\Cms\BannerCategory;
use App\Models\Cms\CatalogCategory;
use App\Models\Cms\ContentCategory;
use App\Models\Cms\DownloadCategory;
use App\Models\Cms\EventCategory;
use App\Models\Cms\FaqCategory;
use App\Models\Cms\FormCategory;
use App\Models\Cms\LocationCategory;
use App\Models\Cms\UserCategory;
use App\Models\Cms\VacancyCategory;

return [
    'banners' => [
        'model' => BannerCategory::class,
        'category_route' => 'banners.categories.',
        'item_route' => 'banners.index',
        'relation' => 'banners',
        'item_title' => 'title',
        'linked_label' => 'Banners',
        'show_url' => false,
    ],
    'catalog' => [
        'model' => CatalogCategory::class,
        'category_route' => 'catalog.categories.',
        'item_route' => 'catalog.index',
        'relation' => 'products',
        'item_title' => 'name',
        'linked_label' => 'Products',
        'show_url' => false,
    ],
    'content' => [
        'model' => ContentCategory::class,
        'category_route' => 'content.categories.',
        'item_route' => 'content.index',
        'relation' => 'contentItems',
        'item_title' => 'title',
        'linked_label' => 'Pages',
        'show_url' => false,
    ],
    'downloads' => [
        'model' => DownloadCategory::class,
        'category_route' => 'downloads.categories.',
        'item_route' => 'downloads.index',
        'relation' => 'downloads',
        'item_title' => 'name',
        'linked_label' => 'Downloads',
        'show_url' => false,
    ],
    'vacancies' => [
        'model' => VacancyCategory::class,
        'category_route' => 'vacancies.categories.',
        'item_route' => 'vacancies.index',
        'relation' => 'vacancies',
        'item_title' => 'title',
        'linked_label' => 'Vacancies',
        'show_url' => false,
    ],
    'events' => [
        'model' => EventCategory::class,
        'category_route' => 'events.categories.',
        'item_route' => 'events.index',
        'relation' => 'events',
        'item_title' => 'title',
        'linked_label' => 'Events',
        'show_url' => false,
    ],
    'faq' => [
        'model' => FaqCategory::class,
        'category_route' => 'faq.categories.',
        'item_route' => 'faq.index',
        'relation' => 'faqItems',
        'item_title' => 'question',
        'linked_label' => 'FAQ items',
        'show_url' => false,
    ],
    'forms' => [
        'model' => FormCategory::class,
        'category_route' => 'forms.categories.',
        'item_route' => 'forms.index',
        'relation' => 'forms',
        'item_title' => 'name',
        'linked_label' => 'Forms',
        'show_url' => false,
    ],
    'locations' => [
        'model' => LocationCategory::class,
        'category_route' => 'locations.categories.',
        'item_route' => 'locations.index',
        'relation' => 'locations',
        'item_title' => 'name',
        'linked_label' => 'Locations',
        'show_url' => false,
    ],
    'users' => [
        'model' => UserCategory::class,
        'category_route' => 'users.categories.',
        'item_route' => 'users.index',
        'relation' => 'users',
        'item_title' => 'name',
        'linked_label' => 'Users',
        'show_url' => false,
    ],
];
