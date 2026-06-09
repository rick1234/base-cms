<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $obsoleteModuleHandles = [
        'orders',
        'sliders',
    ];

    public function up(): void
    {
        $this->deletePermissionsForObsoleteModules();
        $this->deleteObsoleteCmsModules();
    }

    public function down(): void
    {
        //
    }

    private function deletePermissionsForObsoleteModules(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('module_key', $this->obsoleteModuleHandles)
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }

    private function deleteObsoleteCmsModules(): void
    {
        if (! Schema::hasTable('cms_modules')) {
            return;
        }

        DB::table('cms_modules')
            ->whereIn('handle', $this->obsoleteModuleHandles)
            ->delete();
    }
};
