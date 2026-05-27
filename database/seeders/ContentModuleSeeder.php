<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cms\ContentAttachment;
use App\Models\Cms\ContentCategory;
use App\Models\Cms\ContentCategoryImage;
use App\Models\Cms\ContentImage;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Form;
use App\Models\Cms\SliderCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentModuleSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@example.com')->value('id');

        $sliderCategory = SliderCategory::query()->firstOrCreate(
            ['slug' => 'homepage-hero'],
            [
                'name' => 'Homepage hero',
                'description' => 'Seeded slider category used by the rebuilt content module.',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        $contactForm = Form::query()->firstOrCreate(
            ['slug' => 'general-contact'],
            [
                'name' => 'General contact',
                'description' => 'Seeded form placeholder for content item form assignment.',
                'submit_text' => 'Send',
                'success_message' => 'Thanks, your message has been received.',
                'recipient_email' => 'admin@example.com',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        $news = ContentCategory::query()->firstOrCreate(
            ['slug' => 'news'],
            [
                'name' => 'News',
                'description' => 'Seeded news category for content module testing.',
                'meta_description' => 'News and updates managed through the Laravel content module.',
                'slider_category_id' => $sliderCategory->id,
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        $updates = ContentCategory::query()->firstOrCreate(
            ['slug' => 'updates'],
            [
                'parent_id' => $news->id,
                'name' => 'Updates',
                'description' => 'Seeded child category to verify nested category handling.',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        ContentCategoryImage::query()->updateOrCreate(
            [
                'content_category_id' => $news->id,
                'image_path' => 'admin/cms/img/icons/modules/content.svg',
            ],
            [
                'folder' => 'admin/cms/img/icons/modules/',
                'caption' => 'Content category seed image',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        $landingPage = $this->contentItem([
            'title' => 'Welcome to the rebuilt content module',
            'subtitle' => 'Legacy behavior, Laravel structure',
            'slug' => 'welcome-to-the-rebuilt-content-module',
            'intro' => 'This seeded item demonstrates categories, blocks, images, attachments, forms, and slider assignment.',
            'body' => 'Use this record to inspect the recreated admin/content/edit screen after a fresh install.',
            'meta_description' => 'Seeded content item for the Laravel rebuilt content module.',
            'form_id' => $contactForm->id,
            'slider_category_id' => $sliderCategory->id,
            'sort_order' => 1,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        $landingPage->categories()->sync([
            $news->id => ['sort_order' => 1],
            $updates->id => ['sort_order' => 2],
        ]);

        $this->media($landingPage, $adminId);
        $this->blocks($landingPage, $adminId);

        $secondItem = $this->contentItem([
            'title' => 'Second seeded content item',
            'subtitle' => 'Overview and filter data',
            'slug' => 'second-seeded-content-item',
            'intro' => 'This item gives the overview screen more than one row.',
            'body' => 'It is linked only to the Updates category so child-category filters can be checked.',
            'meta_description' => 'Second seeded content item for overview filtering.',
            'sort_order' => 2,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        $secondItem->categories()->sync([
            $updates->id => ['sort_order' => 1],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function contentItem(array $attributes): ContentItem
    {
        return ContentItem::query()->updateOrCreate(
            ['slug' => $attributes['slug']],
            [
                'locale' => 'nl',
                'status' => 'published',
                'active_from' => now()->subDay()->toDateString(),
                'active_until' => now()->addYear()->toDateString(),
                ...$attributes,
            ],
        );
    }

    private function media(ContentItem $contentItem, ?int $adminId): void
    {
        ContentImage::query()->updateOrCreate(
            [
                'content_item_id' => $contentItem->id,
                'image_path' => 'admin/cms/img/icons/modules/content.svg',
            ],
            [
                'folder' => 'admin/cms/img/icons/modules/',
                'caption' => 'Seeded content image',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        ContentAttachment::query()->updateOrCreate(
            [
                'content_item_id' => $contentItem->id,
                'url' => 'admin/cms/img/logo-cms-white.svg',
            ],
            [
                'name' => 'Seeded attachment',
                'type' => 'image/svg+xml',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );
    }

    private function blocks(ContentItem $contentItem, ?int $adminId): void
    {
        $contentItem->forceFill([
            'structured_blocks' => [
                [
                    'type' => 'text',
                    'uuid' => (string) Str::uuid(),
                    'layout' => '100',
                    'data' => [
                        'content' => '<p>This is a seeded text block for the recreated content editor.</p>',
                    ],
                    'settings' => [
                        'alignment' => 'left',
                        'background_style' => 'none',
                        'intro_style' => false,
                    ],
                ],
                [
                    'type' => 'image',
                    'uuid' => (string) Str::uuid(),
                    'layout' => '50',
                    'data' => [
                        'image' => 'admin/cms/img/icons/modules/content.svg',
                        'alt' => 'Seeded content block asset',
                        'caption' => 'Seeded image block',
                    ],
                    'settings' => [
                        'layout' => 'default',
                        'aspect' => 'auto',
                    ],
                ],
                [
                    'type' => 'video',
                    'uuid' => (string) Str::uuid(),
                    'layout' => '50',
                    'data' => [
                        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                        'caption' => 'Seeded video block',
                    ],
                    'settings' => [
                        'provider' => 'youtube',
                    ],
                ],
            ],
            'updated_by' => $adminId,
        ])->save();
    }
}
