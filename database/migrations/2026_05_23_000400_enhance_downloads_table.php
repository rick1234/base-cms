<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('downloads', function (Blueprint $table): void {
            if (! Schema::hasColumn('downloads', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }

            if (! Schema::hasColumn('downloads', 'description')) {
                $table->text('description')->nullable()->after('type');
            }

            if (! Schema::hasColumn('downloads', 'file_disk')) {
                $table->string('file_disk')->default('local')->after('url');
            }

            if (! Schema::hasColumn('downloads', 'file_path')) {
                $table->string('file_path')->nullable()->after('file_disk');
            }

            if (! Schema::hasColumn('downloads', 'original_filename')) {
                $table->string('original_filename')->nullable()->after('file_path');
            }

            if (! Schema::hasColumn('downloads', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('original_filename');
            }

            if (! Schema::hasColumn('downloads', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            }

            if (! Schema::hasColumn('downloads', 'active_from')) {
                $table->date('active_from')->nullable()->after('status');
            }

            if (! Schema::hasColumn('downloads', 'active_until')) {
                $table->date('active_until')->nullable()->after('active_from');
            }

            if (! Schema::hasColumn('downloads', 'is_password_protected')) {
                $table->boolean('is_password_protected')->default(false)->after('active_until');
            }

            if (! Schema::hasColumn('downloads', 'password_hash')) {
                $table->string('password_hash')->nullable()->after('is_password_protected');
            }

            if (! Schema::hasColumn('downloads', 'link_expires_after_minutes')) {
                $table->unsignedInteger('link_expires_after_minutes')->nullable()->after('password_hash');
            }

            if (! Schema::hasColumn('downloads', 'download_count')) {
                $table->unsignedBigInteger('download_count')->default(0)->after('link_expires_after_minutes');
            }

            if (! Schema::hasColumn('downloads', 'last_downloaded_at')) {
                $table->timestamp('last_downloaded_at')->nullable()->after('download_count');
            }
        });

        if (! Schema::hasTable('download_access_tokens')) {
            Schema::create('download_access_tokens', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('download_id')->index();
                $table->string('token_hash')->unique();
                $table->timestamp('expires_at')->nullable()->index();
                $table->unsignedBigInteger('used_count')->default(0);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('download_access_tokens');

        Schema::table('downloads', function (Blueprint $table): void {
            foreach ([
                'slug',
                'description',
                'file_disk',
                'file_path',
                'original_filename',
                'mime_type',
                'file_size',
                'active_from',
                'active_until',
                'is_password_protected',
                'password_hash',
                'link_expires_after_minutes',
                'download_count',
                'last_downloaded_at',
            ] as $column) {
                if (Schema::hasColumn('downloads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
