<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cms\UserCategory;
use App\Models\Cms\CmsPermission;
use App\Models\Cms\CmsRole;
use App\Support\Admin\Roles\ModulePermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserModuleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->first();
        $permissions = app(ModulePermissionRegistry::class)->syncPermissions();

        $administratorRole = CmsRole::query()->firstOrCreate(
            ['slug' => 'administrator'],
            [
                'name' => 'Administrator',
                'description' => 'Seeded administrator role for the rebuilt users module.',
                'status' => 'active',
                'is_system' => true,
                'sort_order' => 1,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );
        $administratorRole->syncPermissionIds($permissions->pluck('id'));

        $editorRole = CmsRole::query()->firstOrCreate(
            ['slug' => 'content-editor'],
            [
                'name' => 'Content editor',
                'description' => 'Can access the admin area and manage content-oriented modules.',
                'status' => 'active',
                'sort_order' => 2,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );
        $editorRole->syncPermissionIds($this->editorPermissionIds());

        $administrators = UserCategory::query()->firstOrCreate(
            ['slug' => 'administrators'],
            [
                'name' => 'Administrators',
                'description' => 'Users with backend access.',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );

        $editors = UserCategory::query()->firstOrCreate(
            ['slug' => 'editors'],
            [
                'name' => 'Editors',
                'description' => 'Users who can manage website content.',
                'status' => 'active',
                'sort_order' => 2,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );

        if ($admin) {
            $admin->forceFill([
                'username' => $admin->username ?: 'admin',
                'locale' => $admin->locale ?: 'nl',
                'is_active' => true,
                'role_id' => $administratorRole->id,
                'created_by' => $admin->created_by ?: $admin->id,
                'updated_by' => $admin->id,
            ])->save();
            $admin->categories()->syncWithoutDetaching([$administrators->id]);
            $admin->roles()->syncWithoutDetaching([$administratorRole->id => ['is_primary' => true]]);
        }

        $editor = User::query()->updateOrCreate(
            ['email' => 'editor@example.com'],
            [
                'name' => 'Base CMS Editor',
                'username' => 'editor',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'is_active' => true,
                'locale' => 'nl',
                'first_name' => 'Base',
                'last_name' => 'Editor',
                'company_name' => 'Base CMS',
                'role_id' => $editorRole->id,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );
        $editor->categories()->syncWithoutDetaching([$editors->id]);
        $editor->roles()->syncWithoutDetaching([$editorRole->id => ['is_primary' => true]]);
    }

    /**
     * @return list<int>
     */
    private function editorPermissionIds(): array
    {
        return CmsPermission::query()
            ->where('permission_key', 'admin.access')
            ->orWhere(function ($query): void {
                $query
                    ->whereIn('module_key', [
                        'content_items',
                        'content_categories',
                        'events',
                        'event_categories',
                        'faq_items',
                        'faq_categories',
                        'forms',
                        'form_categories',
                        'downloads',
                        'download_categories',
                    ])
                    ->whereIn('permission_type', ['module', 'field', 'block']);
            })
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
