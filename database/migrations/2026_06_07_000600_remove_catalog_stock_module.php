<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->deleteStockRegistryRows();

        Schema::dropIfExists('catalog_stock');
    }

    public function down(): void
    {
        //
    }

    private function deleteStockRegistryRows(): void
    {
        if (Schema::hasTable('cms_modules')) {
            $query = DB::table('cms_modules')->where('handle', 'editVoorraad');

            if (Schema::hasColumn('cms_modules', 'table_name')) {
                $query->orWhere('table_name', 'catalog_stock');
            }

            $query->delete();
        }

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $query = DB::table('permissions');
        $hasPermissionFilter = false;

        if (Schema::hasColumn('permissions', 'module_key')) {
            $query->where('module_key', 'catalog_stock');
            $hasPermissionFilter = true;
        }

        if (Schema::hasColumn('permissions', 'name')) {
            $query->orWhere('name', 'like', '%Voorraad%')
                ->orWhere('name', 'like', '%Stock%');
            $hasPermissionFilter = true;
        }

        if (Schema::hasColumn('permissions', 'label')) {
            $query->orWhere('label', 'like', '%Voorraad%')
                ->orWhere('label', 'like', '%Stock%');
            $hasPermissionFilter = true;
        }

        if (! $hasPermissionFilter) {
            return;
        }

        $permissionIds = $query->pluck('id');

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
};
