<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that belong to deferred webshop functionality, not the current catalog CMS.
     *
     * @var list<string>
     */
    private array $deferredWebshopTables = [
        'catalog_review_scores',
        'catalog_review_criteria',
        'catalog_reviews',
        'catalog_coupons',
        'catalog_discounts',
        'catalog_promotions',
        'shipping_costs',
        'order_items',
        'orders',
        'delivery_dates',
        'delivery_days',
        'delivery_settings',
        'country_payment_methods',
    ];

    /**
     * @var list<string>
     */
    private array $deferredPermissionModules = [
        'catalog_promotions',
        'catalog_reviews',
        'catalog_coupons',
        'orders',
        'order_exports',
        'order_delivery_dates',
        'order_payment_methods',
    ];

    public function up(): void
    {
        $this->addCatalogBrandFields();
        $this->dropCatalogPromotionColumn();
        $this->deleteDeferredWebshopPermissions();

        foreach ($this->deferredWebshopTables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('catalog_products') && ! Schema::hasColumn('catalog_products', 'promotion_id')) {
            Schema::table('catalog_products', function (Blueprint $table): void {
                $table->unsignedBigInteger('promotion_id')->nullable()->index()->after('brand_id');
            });
        }
    }

    private function addCatalogBrandFields(): void
    {
        if (! Schema::hasTable('catalog_brands')) {
            return;
        }

        $columns = [
            'website_url' => function (Blueprint $table): void {
                $table->string('website_url')->nullable()->after('description');
            },
            'logo_path' => function (Blueprint $table): void {
                $table->string('logo_path')->nullable()->after('website_url');
            },
            'intro' => function (Blueprint $table): void {
                $table->text('intro')->nullable()->after('logo_path');
            },
            'body' => function (Blueprint $table): void {
                $table->longText('body')->nullable()->after('intro');
            },
            'meta_title' => function (Blueprint $table): void {
                $table->string('meta_title')->nullable()->after('body');
            },
            'meta_description' => function (Blueprint $table): void {
                $table->text('meta_description')->nullable()->after('meta_title');
            },
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn('catalog_brands', $column)) {
                continue;
            }

            Schema::table('catalog_brands', $definition);
        }
    }

    private function dropCatalogPromotionColumn(): void
    {
        if (! Schema::hasTable('catalog_products') || ! Schema::hasColumn('catalog_products', 'promotion_id')) {
            return;
        }

        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropColumn('promotion_id');
        });
    }

    private function deleteDeferredWebshopPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('module_key', $this->deferredPermissionModules)
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }
};
