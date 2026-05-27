<?php

namespace Database\Seeders;

use App\Models\Cms\Module;
use App\Models\Cms\Page;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Base CMS Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'locale' => 'nl',
            ],
        );

        Page::query()->firstOrCreate(
            ['slug' => 'home'],
            [
                'uuid' => (string) Str::uuid(),
                'title' => 'Base CMS',
                'navigation_label' => 'Home',
                'excerpt' => 'A modern Laravel base for custom websites.',
                'body' => 'This page is seeded as a safe starting point for rendered Laravel websites.',
                'meta_title' => 'Base CMS',
                'meta_description' => 'Modern Laravel base for rendered websites, admin functionality, and headless APIs.',
                'template' => 'default',
                'status' => 'published',
                'sort_order' => 0,
                'published_at' => now(),
            ],
        );

        foreach (config('cms_modules.modules') as $handle => $module) {
            Module::query()->firstOrCreate(
                ['handle' => $handle],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'base_owned' => true,
                    'enabled' => true,
                    'sort_order' => array_search($handle, array_keys(config('cms_modules.modules')), true) ?: 0,
                ],
            );
        }

        $this->call(BannerModuleSeeder::class);
        $this->call(ContentModuleSeeder::class);
        $this->call(FormModuleSeeder::class);
        $this->call(EventModuleSeeder::class);
        $this->call(FaqModuleSeeder::class);
        $this->call(DownloadModuleSeeder::class);
        $this->call(LocationModuleSeeder::class);
        $this->call(RedirectModuleSeeder::class);
        $this->call(CatalogModuleSeeder::class);
        $this->call(CountryLanguageSeeder::class);
        $this->call(TranslationModuleSeeder::class);
        $this->call(UserModuleSeeder::class);
    }
}
