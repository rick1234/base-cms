<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cms\Banner;
use App\Models\Cms\BannerCategory;
use App\Models\Cms\BannerImage;
use App\Models\Cms\BannerTranslation;
use Database\Seeders\Support\SeederFiles;
use Illuminate\Database\Seeder;

class BannerModuleSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@example.com')->value('id');

        $hero = BannerCategory::query()->firstOrCreate(
            ['slug' => 'homepage-hero'],
            [
                'name' => 'Homepage hero',
                'description' => 'Homepage hero banners.',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        $promotions = BannerCategory::query()->firstOrCreate(
            ['slug' => 'promotions'],
            [
                'name' => 'Promotions',
                'description' => 'Promotional campaign banners.',
                'status' => 'active',
                'sort_order' => 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        $slider = BannerCategory::query()->firstOrCreate(
            ['slug' => 'slider'],
            [
                'name' => 'Slider',
                'description' => 'Reusable slider banners.',
                'status' => 'active',
                'sort_order' => 3,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        $templateSections = [
            'homepage_hero',
            'homepage_right_block',
            'content_sidebar',
            'footer_banner',
            'catalogus_upsale_banner',
        ];

        Banner::withTrashed()
            ->where('title', 'Seeded banner')
            ->get()
            ->each(function (Banner $legacyBanner): void {
                $legacyBanner->categories()->detach();
                BannerTranslation::query()->where('banner_id', $legacyBanner->id)->delete();
                BannerImage::query()->where('banner_id', $legacyBanner->id)->delete();
                $legacyBanner->forceDelete();
            });

        $titles = [
            ['nl' => 'Zomeractie', 'en' => 'Summer campaign'],
            ['nl' => 'Nieuwe collectie', 'en' => 'New collection'],
            ['nl' => 'Service op maat', 'en' => 'Tailored service'],
            ['nl' => 'Gratis advies', 'en' => 'Free advice'],
            ['nl' => 'Weekend voordeel', 'en' => 'Weekend deal'],
            ['nl' => 'Voor bedrijven', 'en' => 'For businesses'],
            ['nl' => 'Duurzame keuze', 'en' => 'Sustainable choice'],
            ['nl' => 'Populaire producten', 'en' => 'Popular products'],
            ['nl' => 'Plan een afspraak', 'en' => 'Book a meeting'],
            ['nl' => 'Nieuws uitgelicht', 'en' => 'Featured news'],
            ['nl' => 'Lokale expertise', 'en' => 'Local expertise'],
            ['nl' => 'Snelle levering', 'en' => 'Fast delivery'],
            ['nl' => 'Meer inspiratie', 'en' => 'More inspiration'],
            ['nl' => 'Seizoensfavorieten', 'en' => 'Season favorites'],
            ['nl' => 'Start vandaag', 'en' => 'Start today'],
        ];

        for ($index = 1; $index <= 15; $index++) {
            $title = $titles[$index - 1];
            $imagePath = SeederFiles::publicImage(
                'seed-image-01.jpg',
                'admin/uploads/banner',
                sprintf('seeded-banner-%02d.jpg', $index),
            );
            $status = $index % 7 === 0 ? 'draft' : 'published';
            $section = $templateSections[($index - 1) % count($templateSections)];

            $banner = Banner::query()->updateOrCreate(
                ['title' => sprintf('Seeded banner %02d', $index)],
                [
                    'image_path' => $imagePath,
                    'link_url' => $index % 3 === 0 ? '/contact' : '/producten',
                    'button_text' => $index % 3 === 0 ? 'Contact' : 'Bekijk meer',
                    'text' => "Seeded banner {$index} for the rebuilt banner and slider module.",
                    'status' => $status,
                    'starts_at' => now()->subDays($index)->toDateString(),
                    'ends_at' => now()->addMonths(6)->addDays($index)->toDateString(),
                    'sort_order' => $index,
                    'template_section' => $section,
                    'metadata' => [
                        'alt_text' => $title['en'].' image',
                        'target' => '_self',
                    ],
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ],
            );

            $banner->categories()->syncWithoutDetaching([
                ($index <= 5 ? $hero->id : $promotions->id) => ['sort_order' => $index],
                $slider->id => ['sort_order' => $index],
            ]);

            BannerImage::query()->updateOrCreate(
                [
                    'banner_id' => $banner->id,
                    'sort_order' => 1,
                ],
                [
                    'folder' => 'admin/uploads/banner',
                    'image_path' => $imagePath,
                    'caption' => $title['en'],
                    'alt_text' => $title['en'].' image',
                    'title_text' => $title['en'],
                    'description' => "Seeded slider image {$index}.",
                    'original_filename' => sprintf('seeded-banner-%02d.jpg', $index),
                    'mime_type' => 'image/jpeg',
                    'sort_order' => 1,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ],
            );

            foreach ([
                'nl' => [
                    'title' => $title['nl'],
                    'subtitle' => 'Uitgelichte slider',
                    'button_text' => $index % 3 === 0 ? 'Neem contact op' : 'Bekijk meer',
                    'content' => "Voorbeeldtekst voor banner {$index} in de vernieuwde bannermodule.",
                    'alt_text' => $title['nl'].' afbeelding',
                ],
                'en' => [
                    'title' => $title['en'],
                    'subtitle' => 'Featured slider',
                    'button_text' => $index % 3 === 0 ? 'Contact us' : 'Learn more',
                    'content' => "Sample copy for banner {$index} in the rebuilt banner module.",
                    'alt_text' => $title['en'].' image',
                ],
            ] as $locale => $translation) {
                BannerTranslation::query()->updateOrCreate(
                    [
                        'banner_id' => $banner->id,
                        'locale' => $locale,
                    ],
                    [
                        'title' => $translation['title'],
                        'subtitle' => $translation['subtitle'],
                        'link_url' => $index % 3 === 0 ? '/contact' : '/producten',
                        'button_text' => $translation['button_text'],
                        'content' => $translation['content'],
                        'metadata' => ['alt_text' => $translation['alt_text']],
                        'created_by' => $adminId,
                        'updated_by' => $adminId,
                    ],
                );
            }
        }
    }
}
