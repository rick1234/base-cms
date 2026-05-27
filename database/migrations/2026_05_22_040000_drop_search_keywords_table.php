<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('search_keywords');
    }

    public function down(): void
    {
        Schema::create('search_keywords', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('keyword')->index();
            $table->string('remote_ip')->nullable();
            $table->unsignedBigInteger('domain_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->timestamps();
        });
    }
};
