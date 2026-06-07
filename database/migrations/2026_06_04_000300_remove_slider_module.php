<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $table): void {
            if (Schema::hasColumn('content_items', 'slider_category_id')) {
                $table->dropIndex('content_items_slider_category_id_index');
                $table->dropColumn('slider_category_id');
            }
        });

        Schema::table('content_categories', function (Blueprint $table): void {
            if (Schema::hasColumn('content_categories', 'slider_category_id')) {
                $table->dropIndex('content_categories_slider_category_id_index');
                $table->dropColumn('slider_category_id');
            }
        });

        Schema::dropIfExists('slider_category_slider');
        Schema::dropIfExists('sliders');
        Schema::dropIfExists('slider_categories');
    }

    public function down(): void
    {
        Schema::create('slider_categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('name')->index();
            $table->string('slug')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status')->default('active')->index();
            $table->boolean('is_hidden_from_navigation')->default(false);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sliders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('title')->nullable();
            $table->string('slug')->nullable()->index();
            $table->text('text')->nullable();
            $table->string('image_path')->nullable();
            $table->string('link_url')->nullable();
            $table->string('button_text')->nullable();
            $table->string('status')->default('draft')->index();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('slider_category_slider', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->unsignedBigInteger('slider_category_id');
            $table->unsignedBigInteger('slider_id');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->timestamps();
            $table->index('slider_category_id');
            $table->index('slider_id');
            $table->unique(['slider_category_id', 'slider_id'], 'slider_category_slider_unique');
        });

        Schema::table('content_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('content_items', 'slider_category_id')) {
                $table->unsignedBigInteger('slider_category_id')->nullable()->index()->after('form_id');
            }
        });

        Schema::table('content_categories', function (Blueprint $table): void {
            if (! Schema::hasColumn('content_categories', 'slider_category_id')) {
                $table->unsignedBigInteger('slider_category_id')->nullable()->index()->after('image_path');
            }
        });
    }
};
