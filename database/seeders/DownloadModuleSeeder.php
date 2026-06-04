<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cms\Download;
use App\Models\Cms\DownloadCategory;
use Database\Seeders\Support\SeederFiles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

        $publicFile = SeederFiles::privateDocument('seeded-public-download.txt', 'admin/downloads/seeded', 'public-download.txt');
        $protectedFile = SeederFiles::privateDocument('seeded-protected-download.txt', 'admin/downloads/seeded', 'protected-download.txt');

        $publicDownload = Download::query()->updateOrCreate(
            ['slug' => 'seeded-public-download'],
            [
                'name' => 'Seeded public download',
                'description' => 'A seeded private file served through generated download URLs.',
                'type' => $publicFile['extension'],
                'url' => $publicFile['path'],
                'file_disk' => $publicFile['disk'],
                'file_path' => $publicFile['path'],
                'original_filename' => $publicFile['filename'],
                'mime_type' => $publicFile['mime_type'],
                'file_size' => $publicFile['size'],
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
                'type' => $protectedFile['extension'],
                'url' => $protectedFile['path'],
                'file_disk' => $protectedFile['disk'],
                'file_path' => $protectedFile['path'],
                'original_filename' => $protectedFile['filename'],
                'mime_type' => $protectedFile['mime_type'],
                'file_size' => $protectedFile['size'],
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
