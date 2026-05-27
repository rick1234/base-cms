<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_recipients', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->unsignedBigInteger('form_id')->index();
            $table->string('name')->nullable();
            $table->string('email')->index();
            $table->string('type')->default('to')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('form_field_options', function (Blueprint $table): void {
            if (! Schema::hasColumn('form_field_options', 'settings')) {
                $table->json('settings')->nullable()->after('sort_order');
            }
        });

        Schema::table('form_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('form_messages', 'name')) {
                $table->string('name')->nullable()->after('form_id');
            }

            if (! Schema::hasColumn('form_messages', 'is_active')) {
                $table->boolean('is_active')->default(true)->index()->after('type');
            }

            if (! Schema::hasColumn('form_messages', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->index()->after('is_active');
            }

            if (! Schema::hasColumn('form_messages', 'settings')) {
                $table->json('settings')->nullable()->after('sort_order');
            }
        });

        Schema::table('form_submissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('form_submissions', 'status')) {
                $table->string('status')->default('new')->index()->after('form_id');
            }

            if (! Schema::hasColumn('form_submissions', 'source_url')) {
                $table->string('source_url')->nullable()->after('user_agent');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_recipients');

        Schema::table('form_submissions', function (Blueprint $table): void {
            if (Schema::hasColumn('form_submissions', 'source_url')) {
                $table->dropColumn('source_url');
            }

            if (Schema::hasColumn('form_submissions', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('form_messages', function (Blueprint $table): void {
            foreach (['settings', 'sort_order', 'is_active', 'name'] as $column) {
                if (Schema::hasColumn('form_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('form_field_options', function (Blueprint $table): void {
            if (Schema::hasColumn('form_field_options', 'settings')) {
                $table->dropColumn('settings');
            }
        });
    }
};
