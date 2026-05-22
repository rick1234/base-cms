<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('cms_pages')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('navigation_label')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('template')->default('default');
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cms_modules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('handle')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('base_owned')->default(true)->index();
            $table->boolean('enabled')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cms_page_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained('cms_pages')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('cms_modules')->cascadeOnDelete();
            $table->string('region')->default('main')->index();
            $table->json('configuration')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cms_redirect_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('source_path')->unique();
            $table->string('target_url');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('preserve_query')->default(false);
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_redirect_rules');
        Schema::dropIfExists('cms_page_modules');
        Schema::dropIfExists('cms_modules');
        Schema::dropIfExists('cms_pages');
    }
};
