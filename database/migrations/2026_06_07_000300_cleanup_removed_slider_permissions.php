<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('module_key', 'slider_categories')
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

    public function down(): void
    {
        //
    }
};
