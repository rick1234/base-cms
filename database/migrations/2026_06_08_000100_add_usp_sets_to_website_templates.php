<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('website_templates', 'usp_sets')) {
                $table->json('usp_sets')->nullable()->after('defined_sections');
            }
        });
    }

    public function down(): void
    {
        Schema::table('website_templates', function (Blueprint $table): void {
            if (Schema::hasColumn('website_templates', 'usp_sets')) {
                $table->dropColumn('usp_sets');
            }
        });
    }
};
