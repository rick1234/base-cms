<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cms\Banner;
use App\Models\Cms\BannerCategory;
use App\Models\Cms\BannerTranslation;
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

        $banner = Banner::query()->firstOrCreate(
            ['title' => 'Seeded banner'],
            [
                'image_path' => 'admin/cms/img/icons/modules/banner.svg',
                'link_url' => '/contact',
                'button_text' => 'Contact',
                'text' => 'A seeded banner for the rebuilt banner module.',
                'status' => 'published',
                'starts_at' => now()->subDay()->toDateString(),
                'ends_at' => now()->addYear()->toDateString(),
                'sort_order' => 1,
                'metadata' => [
                    'alt_text' => 'Seeded banner image',
                    'target' => '_self',
                ],
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        $banner->categories()->syncWithoutDetaching([
            $hero->id => ['sort_order' => 1],
            $promotions->id => ['sort_order' => 2],
        ]);

        foreach ([
            'nl' => ['title' => 'Seeded banner', 'subtitle' => 'Laravel rebuild', 'button_text' => 'Contact'],
            'en' => ['title' => 'Seeded banner', 'subtitle' => 'Laravel rebuild', 'button_text' => 'Contact'],
        ] as $locale => $translation) {
            BannerTranslation::query()->updateOrCreate(
                [
                    'banner_id' => $banner->id,
                    'locale' => $locale,
                ],
                [
                    'title' => $translation['title'],
                    'subtitle' => $translation['subtitle'],
                    'link_url' => '/contact',
                    'button_text' => $translation['button_text'],
                    'content' => 'A seeded translation for the rebuilt banner module.',
                    'metadata' => ['alt_text' => 'Seeded banner image'],
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ],
            );
        }
    }
}
