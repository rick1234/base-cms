<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_preview_tokens')) {
            return;
        }

        Schema::create('content_preview_tokens', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('content_item_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('token_hash')->unique();
            $table->string('ip_address', 45)->index();
            $table->timestamp('expires_at')->index();
            $table->unsignedBigInteger('used_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_preview_tokens');
    }
};
