<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('radio_schedules');
        Schema::dropIfExists('radio_programs');
        Schema::dropIfExists('radio_guides');
    }

    public function down(): void
    {
        Schema::create('radio_guides', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('name')->index();
            $table->string('slug')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });

        Schema::create('radio_programs', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('title')->index();
            $table->string('slug')->nullable()->index();
            $table->string('locale', 8)->nullable()->index();
            $table->text('intro')->nullable();
            $table->longText('body')->nullable();
            $table->string('image_path')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });

        Schema::create('radio_schedules', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('radio_guide_id')->nullable()->index();
            $table->unsignedBigInteger('radio_program_id')->nullable()->index();
            $table->string('day')->nullable()->index();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });
    }

    private function baseColumns(Blueprint $table): void
    {
        $table->id();
        $table->uuid('uuid')->nullable()->unique();
        $table->unsignedBigInteger('legacy_id')->nullable()->index();
    }

    private function timestamps(Blueprint $table): void
    {
        $table->foreignId('created_by')->nullable()->index();
        $table->foreignId('updated_by')->nullable()->index();
        $table->timestamps();
    }
};
