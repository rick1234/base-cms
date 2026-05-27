<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_images', function (Blueprint $table): void {
            if (! Schema::hasColumn('content_images', 'alt_text')) {
                $table->string('alt_text')->nullable()->after('caption');
            }

            if (! Schema::hasColumn('content_images', 'title_text')) {
                $table->string('title_text')->nullable()->after('alt_text');
            }

            if (! Schema::hasColumn('content_images', 'description')) {
                $table->text('description')->nullable()->after('title_text');
            }

            if (! Schema::hasColumn('content_images', 'credit')) {
                $table->string('credit')->nullable()->after('description');
            }

            if (! Schema::hasColumn('content_images', 'is_decorative')) {
                $table->boolean('is_decorative')->default(false)->after('credit');
            }

            if (! Schema::hasColumn('content_images', 'original_filename')) {
                $table->string('original_filename')->nullable()->after('image_path');
            }

            if (! Schema::hasColumn('content_images', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('original_filename');
            }

            if (! Schema::hasColumn('content_images', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('content_images', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('content_images', 'alt_text') ? 'alt_text' : null,
                Schema::hasColumn('content_images', 'title_text') ? 'title_text' : null,
                Schema::hasColumn('content_images', 'description') ? 'description' : null,
                Schema::hasColumn('content_images', 'credit') ? 'credit' : null,
                Schema::hasColumn('content_images', 'is_decorative') ? 'is_decorative' : null,
                Schema::hasColumn('content_images', 'original_filename') ? 'original_filename' : null,
                Schema::hasColumn('content_images', 'mime_type') ? 'mime_type' : null,
                Schema::hasColumn('content_images', 'file_size') ? 'file_size' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
