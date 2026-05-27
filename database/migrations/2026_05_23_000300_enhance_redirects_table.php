<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('redirects', function (Blueprint $table): void {
            if (! Schema::hasColumn('redirects', 'description')) {
                $table->text('description')->nullable()->after('target_url');
            }

            if (! Schema::hasColumn('redirects', 'hit_count')) {
                $table->unsignedBigInteger('hit_count')->default(0)->after('preserve_query');
            }

            if (! Schema::hasColumn('redirects', 'last_used_at')) {
                $table->timestamp('last_used_at')->nullable()->after('hit_count');
            }

        });
    }

    public function down(): void
    {
        Schema::table('redirects', function (Blueprint $table): void {
            foreach (['description', 'hit_count', 'last_used_at'] as $column) {
                if (Schema::hasColumn('redirects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
