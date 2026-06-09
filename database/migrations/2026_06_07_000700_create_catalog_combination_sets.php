<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropPartialFailedTables();

        if (! Schema::hasTable('catalog_combination_sets')) {
            Schema::create('catalog_combination_sets', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->nullable()->unique();
                $table->unsignedBigInteger('legacy_id')->nullable()->index();
                $table->string('name');
                $table->string('slug')->nullable()->index();
                $table->text('description')->nullable();
                $table->string('status')->default('active')->index();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->foreignId('updated_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('catalog_combination_set_products')) {
            Schema::create('catalog_combination_set_products', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('catalog_combination_set_id');
                $table->unsignedBigInteger('catalog_product_id');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index('catalog_combination_set_id', 'catalog_set_products_set_idx');
                $table->index('catalog_product_id', 'catalog_set_products_product_idx');
                $table->unique(['catalog_combination_set_id', 'catalog_product_id'], 'catalog_set_product_unique');
            });
        }

        $this->migrateLegacyCombinations();

        Schema::dropIfExists('catalog_product_combinations');
    }

    public function down(): void
    {
        //
    }

    private function migrateLegacyCombinations(): void
    {
        if (! Schema::hasTable('catalog_product_combinations')) {
            return;
        }

        $rows = DB::table('catalog_product_combinations')
            ->orderBy('catalog_product_id')
            ->orderBy('sort_order')
            ->get();

        foreach ($rows as $row) {
            $ownerName = DB::table('catalog_products')->where('id', $row->catalog_product_id)->value('name') ?: 'Catalog product';
            $relatedName = DB::table('catalog_products')->where('id', $row->related_product_id)->value('name') ?: 'Related product';
            $name = "Combination: {$ownerName} + {$relatedName}";
            $now = now();

            $setId = DB::table('catalog_combination_sets')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'slug' => Str::slug($name),
                'status' => 'active',
                'sort_order' => (int) ($row->sort_order ?? 0),
                'created_by' => $row->created_by ?? null,
                'updated_by' => $row->updated_by ?? null,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $row->updated_at ?? $now,
            ]);

            foreach ([$row->catalog_product_id, $row->related_product_id] as $index => $productId) {
                DB::table('catalog_combination_set_products')->updateOrInsert(
                    [
                        'catalog_combination_set_id' => $setId,
                        'catalog_product_id' => $productId,
                    ],
                    [
                        'sort_order' => $index + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        }
    }

    private function dropPartialFailedTables(): void
    {
        if (! Schema::hasTable('catalog_product_combinations')
            || ! Schema::hasTable('catalog_combination_sets')
            || ! Schema::hasTable('catalog_combination_set_products')) {
            return;
        }

        if (DB::table('catalog_combination_sets')->exists() || DB::table('catalog_combination_set_products')->exists()) {
            return;
        }

        Schema::dropIfExists('catalog_combination_set_products');
        Schema::dropIfExists('catalog_combination_sets');
    }
};
