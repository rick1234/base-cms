<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('content_items', 'structured_blocks')) {
                $table->json('structured_blocks')->nullable()->after('body');
            }

            if (! Schema::hasColumn('content_items', 'legacy_block_snapshot')) {
                $table->json('legacy_block_snapshot')->nullable()->after('structured_blocks');
            }

            if (! Schema::hasColumn('content_items', 'legacy_blocks_migrated_at')) {
                $table->timestamp('legacy_blocks_migrated_at')->nullable()->after('legacy_block_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('content_items', function (Blueprint $table): void {
            if (Schema::hasColumn('content_items', 'legacy_blocks_migrated_at')) {
                $table->dropColumn('legacy_blocks_migrated_at');
            }

            if (Schema::hasColumn('content_items', 'legacy_block_snapshot')) {
                $table->dropColumn('legacy_block_snapshot');
            }

            if (Schema::hasColumn('content_items', 'structured_blocks')) {
                $table->dropColumn('structured_blocks');
            }
        });
    }
};
