<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables without a reachable admin menu screen in the current CMS.
     *
     * @var list<string>
     */
    private array $unreachableTables = [
        'newsletter_category_newsletter',
        'newsletter_template_blocks',
        'newsletter_categories',
        'newsletter_base_templates',
        'newsletters',
        'category_right_specifications',
        'role_content',
        'role_languages',
        'page_permissions',
        'domain_roles',
        'user_rights',
        'rights',
        'menu_categories',
        'guestbook_entries',
        'geo_locations',
        'short_links',
    ];

    /**
     * @var list<string>
     */
    private array $unreachableModuleHandles = [
        'guestbook',
        'newsletters',
    ];

    public function up(): void
    {
        $this->deleteUnreachableRegistryRows();

        foreach ($this->unreachableTables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        //
    }

    private function deleteUnreachableRegistryRows(): void
    {
        if (Schema::hasTable('cms_modules')) {
            DB::table('cms_modules')
                ->whereIn('handle', $this->unreachableModuleHandles)
                ->delete();
        }

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('module_key', $this->unreachableModuleHandles)
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
};
