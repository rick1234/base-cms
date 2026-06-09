<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('content_items', 'subtitle')) {
                $table->string('subtitle')->nullable()->after('title');
            }

            if (! Schema::hasColumn('content_items', 'form_id')) {
                $table->unsignedBigInteger('form_id')->nullable()->index()->after('meta_description');
            }

        });

        Schema::table('content_categories', function (Blueprint $table): void {
            if (! Schema::hasColumn('content_categories', 'custom_url')) {
                $table->string('custom_url')->nullable()->index()->after('slug');
            }

            if (! Schema::hasColumn('content_categories', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('description');
            }

        });

        if (! Schema::hasTable('content_category_images')) {
            Schema::create('content_category_images', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->nullable()->unique();
                $table->unsignedBigInteger('legacy_id')->nullable()->index();
                $table->unsignedBigInteger('content_category_id')->index();
                $table->string('folder')->nullable();
                $table->string('image_path')->nullable();
                $table->string('caption')->nullable();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->foreignId('updated_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_category_images');

        Schema::table('content_categories', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('content_categories', 'custom_url') ? 'custom_url' : null,
                Schema::hasColumn('content_categories', 'meta_description') ? 'meta_description' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('content_items', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('content_items', 'subtitle') ? 'subtitle' : null,
                Schema::hasColumn('content_items', 'form_id') ? 'form_id' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
