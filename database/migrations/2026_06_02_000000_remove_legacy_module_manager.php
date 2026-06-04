<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const REMOVED_MODULE_HANDLES = [
        'module_manager',
    ];

    private const REMOVED_PERMISSION_MODULES = [
        'module_manager',
        'module_categories',
    ];

    private const REMOVED_TRANSLATION_KEYS = [
        'Edit module',
        'Edit module category',
        'Module Categories',
        'Module Manager',
        'Module category overview',
        'Module overview',
        'ModuleCategorie',
    ];

    public function up(): void
    {
        $this->removeModuleRecords();
        $this->removePermissionRecords();
        $this->removeTranslationRecords();

        Schema::dropIfExists('module_category_module');
        Schema::dropIfExists('module_categories');
    }

    public function down(): void
    {
    }

    private function removeModuleRecords(): void
    {
        if (! Schema::hasTable('cms_modules')) {
            return;
        }

        $moduleIds = DB::table('cms_modules')
            ->whereIn('handle', self::REMOVED_MODULE_HANDLES)
            ->pluck('id');

        if ($moduleIds->isNotEmpty() && Schema::hasTable('cms_page_modules')) {
            DB::table('cms_page_modules')
                ->whereIn('module_id', $moduleIds)
                ->delete();
        }

        DB::table('cms_modules')
            ->whereIn('handle', self::REMOVED_MODULE_HANDLES)
            ->delete();
    }

    private function removePermissionRecords(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = collect();

        if (Schema::hasColumn('permissions', 'module_key')) {
            $permissionIds = $permissionIds->merge(
                DB::table('permissions')
                    ->whereIn('module_key', self::REMOVED_PERMISSION_MODULES)
                    ->pluck('id'),
            );
        }

        if (Schema::hasColumn('permissions', 'permission_key')) {
            $permissionIds = $permissionIds->merge(
                DB::table('permissions')
                    ->where(function ($query): void {
                        foreach (self::REMOVED_PERMISSION_MODULES as $module) {
                            $query->orWhere('permission_key', 'like', "admin.module.{$module}.%")
                                ->orWhere('permission_key', 'like', "admin.field.{$module}.%")
                                ->orWhere('permission_key', 'like', "admin.block.{$module}.%");
                        }
                    })
                    ->pluck('id'),
            );
        }

        $permissionIds = $permissionIds->unique()->values();

        if ($permissionIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('role_permissions') && Schema::hasColumn('role_permissions', 'permission_id')) {
            DB::table('role_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }

    private function removeTranslationRecords(): void
    {
        if (! Schema::hasTable('translation_keys')) {
            return;
        }

        $translationKeyIds = DB::table('translation_keys')
            ->whereIn('key', self::REMOVED_TRANSLATION_KEYS)
            ->pluck('id');

        if ($translationKeyIds->isNotEmpty() && Schema::hasTable('translation_values')) {
            DB::table('translation_values')
                ->whereIn('translation_key_id', $translationKeyIds)
                ->delete();
        }

        DB::table('translation_keys')
            ->whereIn('id', $translationKeyIds)
            ->delete();
    }
};
