<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('translation_keys', function (Blueprint $table): void {
            if (! Schema::hasColumn('translation_keys', 'area')) {
                $table->string('area', 32)->default('shared')->after('id')->index();
            }

            if (! Schema::hasColumn('translation_keys', 'source_locale')) {
                $table->string('source_locale', 16)->default('en')->after('source_text')->index();
            }

            if (! Schema::hasColumn('translation_keys', 'description')) {
                $table->text('description')->nullable()->after('source_locale');
            }

            if (! Schema::hasColumn('translation_keys', 'status')) {
                $table->string('status', 32)->default('active')->after('description')->index();
            }

            if (! Schema::hasColumn('translation_keys', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('status')->index();
            }

            if (! Schema::hasColumn('translation_keys', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('is_system');
            }

            if (! Schema::hasColumn('translation_keys', 'metadata')) {
                $table->json('metadata')->nullable()->after('last_seen_at');
            }
        });

        DB::table('translation_keys')
            ->whereNull('group')
            ->update(['group' => '*']);

        DB::table('translation_keys')
            ->whereNull('source_locale')
            ->update(['source_locale' => 'en']);

        Schema::table('translation_keys', function (Blueprint $table): void {
            $table->dropUnique('translation_keys_key_unique');
            $table->unique(['area', 'group', 'key'], 'translation_keys_area_group_key_unique');
        });

        Schema::table('translation_values', function (Blueprint $table): void {
            if (! Schema::hasColumn('translation_values', 'status')) {
                $table->string('status', 32)->default('active')->after('value')->index();
            }

            if (! Schema::hasColumn('translation_values', 'is_reviewed')) {
                $table->boolean('is_reviewed')->default(false)->after('status')->index();
            }

            if (! Schema::hasColumn('translation_values', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('is_reviewed');
            }

            if (! Schema::hasColumn('translation_values', 'metadata')) {
                $table->json('metadata')->nullable()->after('reviewed_at');
            }

            $table->unique(['translation_key_id', 'locale'], 'translation_values_key_locale_unique');
        });

        DB::table('users')
            ->whereNull('locale')
            ->update(['locale' => 'nl']);

        if (Schema::hasColumn('languages', 'code')) {
            DB::table('languages')->update(['is_default' => false]);
            DB::table('languages')
                ->where('code', 'nl')
                ->update([
                    'status' => 'active',
                    'is_enabled' => true,
                    'is_default' => true,
                ]);
            DB::table('languages')
                ->where('code', 'en')
                ->update([
                    'status' => 'active',
                    'is_enabled' => true,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('translation_values', function (Blueprint $table): void {
            $table->dropUnique('translation_values_key_locale_unique');
            $table->dropColumn(['status', 'is_reviewed', 'reviewed_at', 'metadata']);
        });

        Schema::table('translation_keys', function (Blueprint $table): void {
            $table->dropUnique('translation_keys_area_group_key_unique');
            $table->unique('key', 'translation_keys_key_unique');
            $table->dropColumn([
                'area',
                'source_locale',
                'description',
                'status',
                'is_system',
                'last_seen_at',
                'metadata',
            ]);
        });
    }
};
