<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('navigation_menus')) {
            Schema::create('navigation_menus', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('domain_id')->nullable()->constrained('domains')->nullOnDelete();
                $table->string('handle')->index();
                $table->string('name');
                $table->string('locale', 8)->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['domain_id', 'handle', 'locale'], 'navigation_menus_domain_handle_locale_unique');
            });
        }

        if (! Schema::hasTable('navigation_menu_items')) {
            Schema::create('navigation_menu_items', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('navigation_menu_id')->constrained('navigation_menus')->cascadeOnDelete();
                $table->unsignedBigInteger('parent_id')->nullable()->index();
                $table->string('title');
                $table->string('link_type')->default('custom')->index();
                $table->unsignedBigInteger('link_id')->nullable()->index();
                $table->string('custom_url')->nullable();
                $table->boolean('opens_new_tab')->default(false);
                $table->boolean('expand_children')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->foreign('parent_id')
                    ->references('id')
                    ->on('navigation_menu_items')
                    ->cascadeOnDelete();
                $table->index(['navigation_menu_id', 'parent_id', 'sort_order'], 'navigation_items_menu_parent_sort_index');
                $table->index(['link_type', 'link_id'], 'navigation_items_link_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_menu_items');
        Schema::dropIfExists('navigation_menus');
    }
};
