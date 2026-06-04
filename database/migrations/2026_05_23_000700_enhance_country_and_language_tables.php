<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table): void {
            $table->string('iso2', 2)->nullable()->after('slug')->unique();
            $table->string('iso3', 3)->nullable()->after('iso2')->index();
            $table->string('numeric_code', 3)->nullable()->after('iso3')->index();
            $table->string('currency_code', 3)->nullable()->after('numeric_code')->index();
            $table->string('region_code', 8)->nullable()->after('currency_code')->index();
            $table->boolean('charges_vat')->default(false)->after('region_code');
            $table->json('timezones')->nullable()->after('charges_vat');
            $table->string('source_locale', 16)->default('en')->after('timezones');
            $table->boolean('is_enabled')->default(true)->after('source_locale')->index();
        });

        Schema::table('languages', function (Blueprint $table): void {
            $table->string('code', 16)->nullable()->after('slug')->unique();
            $table->string('native_name')->nullable()->after('code');
            $table->string('direction', 3)->default('ltr')->after('native_name');
            $table->string('fallback_locale', 16)->nullable()->after('direction');
            $table->string('source_locale', 16)->default('en')->after('fallback_locale');
            $table->boolean('is_enabled')->default(false)->after('source_locale')->index();
            $table->boolean('is_default')->default(false)->after('is_enabled')->index();
        });

        Schema::table('iso_languages', function (Blueprint $table): void {
            $table->string('code', 16)->nullable()->after('slug')->unique();
            $table->string('native_name')->nullable()->after('code');
            $table->string('direction', 3)->default('ltr')->after('native_name');
            $table->string('source_locale', 16)->default('en')->after('direction');
        });
    }

    public function down(): void
    {
        Schema::table('iso_languages', function (Blueprint $table): void {
            $table->dropUnique('iso_languages_code_unique');
            $table->dropColumn(['code', 'native_name', 'direction', 'source_locale']);
        });

        Schema::table('languages', function (Blueprint $table): void {
            $table->dropUnique('languages_code_unique');
            $table->dropColumn([
                'code',
                'native_name',
                'direction',
                'fallback_locale',
                'source_locale',
                'is_enabled',
                'is_default',
            ]);
        });

        Schema::table('countries', function (Blueprint $table): void {
            $table->dropUnique('countries_iso2_unique');
            $table->dropColumn([
                'iso2',
                'iso3',
                'numeric_code',
                'currency_code',
                'region_code',
                'charges_vat',
                'timezones',
                'source_locale',
                'is_enabled',
            ]);
        });
    }
};
