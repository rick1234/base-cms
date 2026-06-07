<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cms\ContentAttachment;
use App\Models\Cms\ContentCategory;
use App\Models\Cms\ContentCategoryImage;
use App\Models\Cms\ContentImage;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Form;
use Database\Seeders\Support\SeederFiles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentModuleSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@example.com')->value('id');

        $contactForms = [];

        foreach ($this->contactForms() as $formData) {
            $contactForms[$formData['locale']] = Form::query()->updateOrCreate(
                ['slug' => $formData['slug']],
                [
                    ...$formData,
                    'recipient_email' => 'admin@example.com',
                    'status' => 'active',
                    'sort_order' => $formData['locale'] === 'nl' ? 1 : 2,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ],
            );
        }

        $news = ContentCategory::query()->firstOrCreate(
            ['slug' => 'news'],
            [
                'name' => 'News',
                'description' => 'Seeded news category for content module testing.',
                'meta_description' => 'News and updates managed through the Laravel content module.',
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

        $categoryImagePath = SeederFiles::publicImage('seed-image-02.png', 'content/category-images', 'seeded-content-category.png');

        ContentCategoryImage::query()->updateOrCreate(
            [
                'content_category_id' => $news->id,
                'image_path' => $categoryImagePath,
            ],
            [
                'folder' => 'storage/content/category-images/',
                'caption' => 'Content category seed image',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        foreach ($this->contentItems() as $item) {
            $contentItem = $this->contentItem([
                ...$item,
                'form_id' => ($item['uses_form'] ?? false) ? $contactForms[$item['locale']]->id : null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);

            if ($item['kind'] === 'landing') {
                $contentItem->categories()->sync([
                    $news->id => ['sort_order' => 1],
                    $updates->id => ['sort_order' => 2],
                ]);

                $this->media($contentItem, $adminId, $item['locale']);
                $this->blocks($contentItem, $adminId, $item['locale']);

                continue;
            }

            $contentItem->categories()->sync([
                $updates->id => ['sort_order' => 1],
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function contactForms(): array
    {
        return [
            [
                'name' => 'Algemeen contact',
                'slug' => 'general-contact',
                'locale' => 'nl',
                'description' => 'Voorbeeldformulier voor koppeling aan een pagina.',
                'submit_text' => 'Versturen',
                'success_message' => 'Bedankt, je bericht is ontvangen.',
            ],
            [
                'name' => 'General contact',
                'slug' => 'general-contact-en',
                'locale' => 'en',
                'description' => 'Seeded form placeholder for content item form assignment.',
                'submit_text' => 'Send',
                'success_message' => 'Thanks, your message has been received.',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function contentItems(): array
    {
        return [
            [
                'kind' => 'landing',
                'locale' => 'nl',
                'title' => 'Welkom bij de vernieuwde paginamodule',
                'subtitle' => 'Legacy gedrag, Laravel structuur',
                'slug' => 'welcome-to-the-rebuilt-content-module',
                'meta_description' => 'Voorbeeldpagina voor de vernieuwde Laravel paginamodule.',
                'uses_form' => true,
                'sort_order' => 1,
            ],
            [
                'kind' => 'landing',
                'locale' => 'en',
                'title' => 'Welcome to the rebuilt page module',
                'subtitle' => 'Legacy behavior, Laravel structure',
                'slug' => 'welcome-to-the-rebuilt-content-module-en',
                'meta_description' => 'Seeded content item for the Laravel rebuilt page module.',
                'uses_form' => true,
                'sort_order' => 2,
            ],
            [
                'kind' => 'overview',
                'locale' => 'nl',
                'title' => 'Tweede voorbeeldpagina',
                'subtitle' => 'Overzicht en filterdata',
                'slug' => 'second-seeded-content-item',
                'meta_description' => 'Tweede voorbeeldpagina voor overzichtsfilters.',
                'sort_order' => 3,
            ],
            [
                'kind' => 'overview',
                'locale' => 'en',
                'title' => 'Second seeded page',
                'subtitle' => 'Overview and filter data',
                'slug' => 'second-seeded-content-item-en',
                'meta_description' => 'Second seeded content item for overview filtering.',
                'sort_order' => 4,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function contentItem(array $attributes): ContentItem
    {
        return ContentItem::query()->updateOrCreate(
            ['slug' => $attributes['slug']],
            [
                'status' => 'published',
                'active_from' => now()->subDay()->toDateString(),
                'active_until' => now()->addYear()->toDateString(),
                ...collect($attributes)->except(['kind', 'uses_form'])->all(),
            ],
        );
    }

    private function media(ContentItem $contentItem, ?int $adminId, string $locale): void
    {
        $imagePath = SeederFiles::publicImage('seed-image-03.jpg', 'content/images', 'seeded-content-'.$locale.'.jpg');
        $attachmentPath = SeederFiles::publicDocument('content-attachment.txt', 'content/attachments', 'seeded-content-'.$locale.'.txt');

        ContentImage::query()->updateOrCreate(
            [
                'content_item_id' => $contentItem->id,
                'image_path' => $imagePath,
            ],
            [
                'folder' => 'storage/content/images/',
                'caption' => $locale === 'nl' ? 'Voorbeeldafbeelding pagina' : 'Seeded content image',
                'original_filename' => basename($imagePath),
                'mime_type' => 'image/jpeg',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        ContentAttachment::query()->updateOrCreate(
            [
                'content_item_id' => $contentItem->id,
                'url' => $attachmentPath,
            ],
            [
                'name' => $locale === 'nl' ? 'Voorbeeldbijlage' : 'Seeded attachment',
                'type' => 'text/plain',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );
    }

    private function blocks(ContentItem $contentItem, ?int $adminId, string $locale): void
    {
        $blockImagePath = SeederFiles::publicImage('seed-image-04.jpg', 'content/blocks', 'seeded-content-block-'.$locale.'.jpg');

        $texts = [
            'nl' => [
                'content' => '<p>Dit is een voorbeeldtekstblok voor de vernieuwde pagina-editor.</p>',
                'alt' => 'Voorbeeldbestand voor paginablok',
                'image_caption' => 'Voorbeeldafbeelding',
                'video_caption' => 'Voorbeeldvideo',
            ],
            'en' => [
                'content' => '<p>This is a seeded text block for the recreated page editor.</p>',
                'alt' => 'Seeded content block asset',
                'image_caption' => 'Seeded image block',
                'video_caption' => 'Seeded video block',
            ],
        ][$locale];

        $contentItem->forceFill([
            'structured_blocks' => [
                [
                    'type' => 'text',
                    'uuid' => (string) Str::uuid(),
                    'layout' => '100',
                    'data' => [
                        'content' => $texts['content'],
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
                        'image' => $blockImagePath,
                        'alt' => $texts['alt'],
                        'caption' => $texts['image_caption'],
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
                        'caption' => $texts['video_caption'],
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
