<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $removedColumns = [
        'price_note',
        'is_on_sale',
        'sale_starts_at',
        'sale_ends_at',
        'sale_price',
        'sale_price_note',
        'can_be_engraved',
    ];

    public function up(): void
    {
        foreach ($this->removedColumns as $column) {
            if (! Schema::hasColumn('catalog_products', $column)) {
                continue;
            }

            Schema::table('catalog_products', function (Blueprint $table) use ($column): void {
                $table->dropColumn($column);
            });
        }
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            if (! Schema::hasColumn('catalog_products', 'price_note')) {
                $table->text('price_note')->nullable()->after('price');
            }

            if (! Schema::hasColumn('catalog_products', 'is_on_sale')) {
                $table->boolean('is_on_sale')->default(false)->index()->after('price_note');
            }

            if (! Schema::hasColumn('catalog_products', 'sale_starts_at')) {
                $table->date('sale_starts_at')->nullable()->after('is_on_sale');
            }

            if (! Schema::hasColumn('catalog_products', 'sale_ends_at')) {
                $table->date('sale_ends_at')->nullable()->after('sale_starts_at');
            }

            if (! Schema::hasColumn('catalog_products', 'sale_price')) {
                $table->integer('sale_price')->nullable()->after('sale_ends_at');
            }

            if (! Schema::hasColumn('catalog_products', 'sale_price_note')) {
                $table->text('sale_price_note')->nullable()->after('sale_price');
            }

            if (! Schema::hasColumn('catalog_products', 'can_be_engraved')) {
                $table->boolean('can_be_engraved')->default(false)->after('brand_id');
            }
        });
    }
};
