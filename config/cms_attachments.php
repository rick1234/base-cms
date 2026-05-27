<?php

use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogProductAttachment;
use App\Models\Cms\ContentAttachment;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Event;
use App\Models\Cms\EventAttachment;
use App\Models\Cms\FaqAttachment;
use App\Models\Cms\FaqItem;

return [
    'content' => [
        'model' => ContentItem::class,
        'attachment_model' => ContentAttachment::class,
        'relation' => 'attachments',
        'foreign_key' => 'content_item_id',
        'storage_directory' => 'content/attachments',
        'max_upload_kilobytes' => 20480,
    ],

    'events' => [
        'model' => Event::class,
        'attachment_model' => EventAttachment::class,
        'relation' => 'attachments',
        'foreign_key' => 'event_id',
        'storage_directory' => 'events/attachments',
        'max_upload_kilobytes' => 20480,
    ],

    'faq' => [
        'model' => FaqItem::class,
        'attachment_model' => FaqAttachment::class,
        'relation' => 'attachments',
        'foreign_key' => 'faq_item_id',
        'storage_directory' => 'admin/uploads/faq/attachments',
        'max_upload_kilobytes' => 10240,
    ],

    'catalog' => [
        'model' => CatalogProduct::class,
        'attachment_model' => CatalogProductAttachment::class,
        'relation' => 'attachments',
        'foreign_key' => 'catalog_product_id',
        'storage_directory' => 'admin/uploads/catalog/attachments',
        'max_upload_kilobytes' => 10240,
    ],
];
