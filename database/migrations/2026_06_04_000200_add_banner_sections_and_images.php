<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('website_templates', 'defined_sections')) {
                $table->json('defined_sections')->nullable()->after('default_settings');
            }
        });

        Schema::table('banners', function (Blueprint $table): void {
            if (! Schema::hasColumn('banners', 'template_section')) {
                $table->string('template_section')->nullable()->after('sort_order')->index();
            }
        });

        Schema::create('banner_images', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->unsignedBigInteger('banner_id')->index();
            $table->string('folder')->nullable();
            $table->string('image_path')->nullable();
            $table->string('caption')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('title_text')->nullable();
            $table->text('description')->nullable();
            $table->string('credit')->nullable();
            $table->boolean('is_decorative')->default(false);
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_images');

        Schema::table('banners', function (Blueprint $table): void {
            if (Schema::hasColumn('banners', 'template_section')) {
                $table->dropColumn('template_section');
            }
        });

        Schema::table('website_templates', function (Blueprint $table): void {
            if (Schema::hasColumn('website_templates', 'defined_sections')) {
                $table->dropColumn('defined_sections');
            }
        });
    }
};
