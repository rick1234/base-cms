<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'content_items',
        'events',
        'faq_items',
        'vacancies',
        'locations',
        'catalog_products',
        'catalog_categories',
        'download_categories',
        'form_categories',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'meta_title')) {
                    $table->string('meta_title')->nullable();
                }

                if (! Schema::hasColumn($tableName, 'og_title')) {
                    $table->string('og_title')->nullable();
                }

                if (! Schema::hasColumn($tableName, 'og_description')) {
                    $table->text('og_description')->nullable();
                }

                if (! Schema::hasColumn($tableName, 'og_image')) {
                    $table->string('og_image')->nullable();
                }

                if (! Schema::hasColumn($tableName, 'canonical_url')) {
                    $table->string('canonical_url')->nullable();
                }

                if (! Schema::hasColumn($tableName, 'robots')) {
                    $table->string('robots')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                foreach (['meta_title', 'og_title', 'og_description', 'og_image', 'canonical_url', 'robots'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
