<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('content_items')) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('content_items', 'intro') ? 'intro' : null,
            Schema::hasColumn('content_items', 'body') ? 'body' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('content_items', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('content_items')) {
            return;
        }

        if (! Schema::hasColumn('content_items', 'intro')) {
            Schema::table('content_items', function (Blueprint $table): void {
                $table->text('intro')->nullable()->after('locale');
            });
        }

        if (! Schema::hasColumn('content_items', 'body')) {
            Schema::table('content_items', function (Blueprint $table): void {
                $after = Schema::hasColumn('content_items', 'intro') ? 'intro' : 'locale';

                $table->longText('body')->nullable()->after($after);
            });
        }
    }
};
