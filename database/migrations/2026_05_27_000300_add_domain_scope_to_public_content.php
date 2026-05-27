<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $publicTables = [
        'content_items',
        'events',
        'faq_items',
        'vacancies',
        'locations',
        'catalog_products',
        'forms',
    ];

    public function up(): void
    {
        if (Schema::hasTable('cms_pages')) {
            Schema::table('cms_pages', function (Blueprint $table): void {
                if (! Schema::hasColumn('cms_pages', 'domain_id')) {
                    $table->foreignId('domain_id')->nullable()->after('parent_id')->index();
                }
            });

            Schema::table('cms_pages', function (Blueprint $table): void {
                $table->dropUnique('cms_pages_slug_unique');
                $table->unique(['domain_id', 'slug'], 'cms_pages_domain_slug_unique');
            });
        }

        foreach ($this->publicTables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'domain_id')) {
                    $table->foreignId('domain_id')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cms_pages')) {
            Schema::table('cms_pages', function (Blueprint $table): void {
                $table->dropUnique('cms_pages_domain_slug_unique');
                $table->unique('slug', 'cms_pages_slug_unique');

                if (Schema::hasColumn('cms_pages', 'domain_id')) {
                    $table->dropColumn('domain_id');
                }
            });
        }

        foreach ($this->publicTables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'domain_id')) {
                    $table->dropColumn('domain_id');
                }
            });
        }
    }
};
