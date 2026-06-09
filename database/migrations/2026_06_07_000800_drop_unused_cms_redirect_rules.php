<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cms_redirect_rules');
    }

    public function down(): void
    {
        if (Schema::hasTable('cms_redirect_rules')) {
            return;
        }

        Schema::create('cms_redirect_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('source_path')->unique();
            $table->string('target_url');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('preserve_query')->default(false);
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();
        });
    }
};
