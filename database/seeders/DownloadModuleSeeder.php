<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cms\Download;
use App\Models\Cms\DownloadCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DownloadModuleSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@example.com')->value('id');

        $rootCategory = DownloadCategory::query()->firstOrCreate(
            ['slug' => 'downloads'],
            [
                'name' => 'Downloads',
                'description' => 'Seeded root category for secure downloads.',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        $protectedCategory = DownloadCategory::query()->firstOrCreate(
            ['slug' => 'protected-downloads'],
            [
                'parent_id' => $rootCategory->id,
                'name' => 'Protected downloads',
                'description' => 'Seeded category for password protected files.',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        $publicPath = 'admin/downloads/seeded/public-download.txt';
        $protectedPath = 'admin/downloads/seeded/protected-download.txt';

        Storage::disk('local')->put($publicPath, 'Seeded public download for the Laravel base CMS.');
        Storage::disk('local')->put($protectedPath, 'Seeded password protected download for the Laravel base CMS.');

        $publicDownload = Download::query()->updateOrCreate(
            ['slug' => 'seeded-public-download'],
            [
                'name' => 'Seeded public download',
                'description' => 'A seeded private file served through generated download URLs.',
                'type' => 'txt',
                'url' => $publicPath,
                'file_disk' => 'local',
                'file_path' => $publicPath,
                'original_filename' => 'public-download.txt',
                'mime_type' => 'text/plain',
                'file_size' => Storage::disk('local')->size($publicPath),
                'status' => 'active',
                'active_from' => now()->subDay()->toDateString(),
                'active_until' => now()->addYear()->toDateString(),
                'is_password_protected' => false,
                'password_hash' => null,
                'link_expires_after_minutes' => 60,
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        $protectedDownload = Download::query()->updateOrCreate(
            ['slug' => 'seeded-protected-download'],
            [
                'name' => 'Seeded protected download',
                'description' => 'A seeded file that requires a password before a temporary URL is issued.',
                'type' => 'txt',
                'url' => $protectedPath,
                'file_disk' => 'local',
                'file_path' => $protectedPath,
                'original_filename' => 'protected-download.txt',
                'mime_type' => 'text/plain',
                'file_size' => Storage::disk('local')->size($protectedPath),
                'status' => 'active',
                'active_from' => now()->subDay()->toDateString(),
                'active_until' => now()->addYear()->toDateString(),
                'is_password_protected' => true,
                'password_hash' => Hash::make('download-password'),
                'link_expires_after_minutes' => null,
                'sort_order' => 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        $publicDownload->categories()->sync([
            $rootCategory->id => ['sort_order' => 1],
        ]);

        $protectedDownload->categories()->sync([
            $rootCategory->id => ['sort_order' => 1],
            $protectedCategory->id => ['sort_order' => 2],
        ]);
    }
}
