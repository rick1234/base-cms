<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('faq_videos');
        Schema::dropIfExists('faq_images');
    }

    public function down(): void
    {
        if (! Schema::hasTable('faq_images')) {
            Schema::create('faq_images', function (Blueprint $table): void {
                $this->baseColumns($table);
                $table->unsignedBigInteger('faq_item_id')->index();
                $table->string('folder')->nullable();
                $table->string('image_path')->nullable();
                $table->string('caption')->nullable();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $this->timestamps($table);
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('faq_videos')) {
            Schema::create('faq_videos', function (Blueprint $table): void {
                $this->baseColumns($table);
                $table->unsignedBigInteger('faq_item_id')->index();
                $table->string('title')->nullable();
                $table->string('url');
                $table->string('provider')->nullable();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $this->timestamps($table);
            });
        }
    }

    private function baseColumns(Blueprint $table): void
    {
        $table->id();
        $table->uuid('uuid')->nullable()->unique();
        $table->unsignedBigInteger('legacy_id')->nullable()->index();
    }

    private function timestamps(Blueprint $table): void
    {
        $table->foreignId('created_by')->nullable()->index();
        $table->foreignId('updated_by')->nullable()->index();
        $table->timestamps();
    }
};
