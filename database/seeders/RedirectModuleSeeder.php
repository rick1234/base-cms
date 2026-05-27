<?php

namespace Database\Seeders;

use App\Models\Cms\CmsRedirect;
use Illuminate\Database\Seeder;

class RedirectModuleSeeder extends Seeder
{
    public function run(): void
    {
        CmsRedirect::query()->updateOrCreate(
            ['source_path' => 'old-base-cms'],
            [
                'target_url' => '/',
                'description' => 'Seeded redirect used to verify the rebuilt redirects module.',
                'status_code' => 301,
                'is_active' => true,
                'preserve_query' => true,
            ],
        );

        CmsRedirect::query()->updateOrCreate(
            ['source_path' => 'temporary-campaign'],
            [
                'target_url' => '/campaign',
                'description' => 'Seeded temporary redirect example.',
                'status_code' => 302,
                'is_active' => false,
                'preserve_query' => false,
            ],
        );
    }
}
