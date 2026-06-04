<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = array_values(array_filter([
            'shipping_general_cents',
            'shipping_envelope_cents',
            'shipping_small_box_cents',
            'shipping_big_box_cents',
        ], fn (string $column): bool => Schema::hasColumn('countries', $column)));

        if ($columns === []) {
            return;
        }

        Schema::table('countries', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('countries', 'shipping_general_cents')) {
            Schema::table('countries', function (Blueprint $table): void {
                $table->unsignedInteger('shipping_general_cents')->nullable()->after('charges_vat');
            });
        }

        if (! Schema::hasColumn('countries', 'shipping_envelope_cents')) {
            Schema::table('countries', function (Blueprint $table): void {
                $table->unsignedInteger('shipping_envelope_cents')->nullable()->after('shipping_general_cents');
            });
        }

        if (! Schema::hasColumn('countries', 'shipping_small_box_cents')) {
            Schema::table('countries', function (Blueprint $table): void {
                $table->unsignedInteger('shipping_small_box_cents')->nullable()->after('shipping_envelope_cents');
            });
        }

        if (! Schema::hasColumn('countries', 'shipping_big_box_cents')) {
            Schema::table('countries', function (Blueprint $table): void {
                $table->unsignedInteger('shipping_big_box_cents')->nullable()->after('shipping_small_box_cents');
            });
        }
    }
};
