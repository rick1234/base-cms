<?php

namespace Database\Seeders;

use App\Models\Cms\FaqAttachment;
use App\Models\Cms\FaqCategory;
use App\Models\Cms\FaqImage;
use App\Models\Cms\FaqItem;
use App\Models\Cms\FaqVideo;
use Illuminate\Database\Seeder;

class FaqModuleSeeder extends Seeder
{
    public function run(): void
    {
        $rootCategory = FaqCategory::query()->firstOrCreate(
            ['slug' => 'faq'],
            [
                'name' => 'FAQ',
                'description' => 'Seeded FAQ root category.',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );

        $supportCategory = FaqCategory::query()->firstOrCreate(
            ['slug' => 'support'],
            [
                'parent_id' => $rootCategory->id,
                'name' => 'Support',
                'description' => 'Seeded support FAQ category.',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );

        $faqItem = FaqItem::query()->updateOrCreate(
            ['slug' => 'seeded-faq-question'],
            [
                'question' => 'Seeded FAQ question',
                'locale' => 'nl',
                'intro' => 'A seeded FAQ intro.',
                'body' => 'This seeded FAQ item verifies the rebuilt FAQ module.',
                'meta_description' => 'Seeded FAQ item for the Laravel base CMS.',
                'status' => 'published',
                'active_from' => now()->toDateString(),
                'sort_order' => 1,
            ],
        );

        $faqItem->categories()->sync([
            $rootCategory->id => ['sort_order' => 1],
            $supportCategory->id => ['sort_order' => 2],
        ]);

        FaqImage::query()->updateOrCreate(
            ['faq_item_id' => $faqItem->id, 'image_path' => 'admin/cms/img/icons/modules/faq.svg'],
            [
                'folder' => 'admin/cms/img/icons/modules',
                'caption' => 'Seeded FAQ image',
                'sort_order' => 1,
            ],
        );

        FaqAttachment::query()->updateOrCreate(
            ['faq_item_id' => $faqItem->id, 'url' => 'admin/cms/img/icons/modules/faq.svg'],
            [
                'name' => 'Seeded FAQ attachment',
                'type' => 'image/svg+xml',
                'sort_order' => 1,
            ],
        );

        FaqVideo::query()->updateOrCreate(
            ['faq_item_id' => $faqItem->id, 'url' => 'https://example.com/faq-video'],
            [
                'title' => 'Seeded FAQ video',
                'provider' => 'external',
                'sort_order' => 1,
            ],
        );
    }
}
