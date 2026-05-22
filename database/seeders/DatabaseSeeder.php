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

        foreach (config('wms.modules') as $handle => $module) {
            Module::query()->firstOrCreate(
                ['handle' => $handle],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'base_owned' => true,
                    'enabled' => true,
                    'sort_order' => array_search($handle, array_keys(config('wms.modules')), true) ?: 0,
                ],
            );
        }
    }
}
