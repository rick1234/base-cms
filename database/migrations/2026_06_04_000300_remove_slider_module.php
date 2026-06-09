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
        //
    }
};
