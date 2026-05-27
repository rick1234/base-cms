<?php

namespace Database\Seeders;

use App\Models\Cms\CatalogBrand;
use App\Models\Cms\CatalogCategory;
use App\Models\Cms\CatalogCoupon;
use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogProductAttachment;
use App\Models\Cms\CatalogProductImage;
use App\Models\Cms\CatalogProductOption;
use App\Models\Cms\CatalogProductTranslation;
use App\Models\Cms\CatalogProductVideo;
use App\Models\Cms\CatalogPromotion;
use App\Models\Cms\CatalogReview;
use App\Models\Cms\CatalogStock;
use Illuminate\Database\Seeder;

class CatalogModuleSeeder extends Seeder
{
    public function run(): void
    {
        $rootCategory = CatalogCategory::query()->firstOrCreate(
            ['slug' => 'catalog'],
            [
                'name' => 'Catalog',
                'description' => 'Seeded catalog root category.',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );

        $featuredCategory = CatalogCategory::query()->firstOrCreate(
            ['slug' => 'featured-products'],
            [
                'parent_id' => $rootCategory->id,
                'name' => 'Featured products',
                'description' => 'Seeded featured products category.',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );

        $brand = CatalogBrand::query()->firstOrCreate(
            ['slug' => 'base-brand'],
            [
                'name' => 'Base Brand',
                'description' => 'Seeded catalog brand.',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );

        $promotion = CatalogPromotion::query()->firstOrCreate(
            ['slug' => 'launch-promotion'],
            [
                'name' => 'Launch promotion',
                'description' => 'Seeded launch promotion.',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );

        $primary = CatalogProduct::query()->updateOrCreate(
            ['sku' => 'BASE-001'],
            [
                'name' => 'Seeded catalog product',
                'description' => 'A seeded product that exercises the rebuilt catalog module.',
                'price' => 2995,
                'price_note' => 'Includes starter configuration.',
                'is_on_sale' => true,
                'sale_price' => 2495,
                'sale_price_note' => 'Launch offer.',
                'meta_description' => 'Seeded catalog product for the Laravel base CMS.',
                'brand_id' => $brand->id,
                'promotion_id' => $promotion->id,
                'can_be_engraved' => false,
                'status' => 'published',
                'active_from' => now()->toDateString(),
            ],
        );

        $secondary = CatalogProduct::query()->updateOrCreate(
            ['sku' => 'BASE-002'],
            [
                'name' => 'Seeded related product',
                'description' => 'A related catalog product for combination testing.',
                'price' => 1495,
                'brand_id' => $brand->id,
                'status' => 'published',
                'active_from' => now()->toDateString(),
            ],
        );

        $primary->categories()->sync([
            $rootCategory->id => ['sort_order' => 1],
            $featuredCategory->id => ['sort_order' => 2],
        ]);
        $secondary->categories()->sync([
            $featuredCategory->id => ['sort_order' => 1],
        ]);
        $primary->relatedProducts()->sync([
            $secondary->id => ['sort_order' => 1],
        ]);

        CatalogProductImage::query()->updateOrCreate(
            ['catalog_product_id' => $primary->id, 'image_path' => 'admin/cms/img/icons/modules/catalogus.svg'],
            [
                'folder' => 'admin/cms/img/icons/modules',
                'caption' => 'Seeded catalog product',
                'sort_order' => 1,
            ],
        );

        CatalogProductAttachment::query()->updateOrCreate(
            ['catalog_product_id' => $primary->id, 'url' => 'admin/cms/img/icons/modules/catalogus.svg'],
            [
                'name' => 'Seeded catalog sheet',
                'type' => 'image/svg+xml',
                'sort_order' => 1,
            ],
        );

        CatalogProductOption::query()->updateOrCreate(
            ['catalog_product_id' => $primary->id, 'locale' => 'nl', 'label' => 'Kleur'],
            ['value' => 'Zwart', 'updated_by' => null],
        );

        CatalogProductTranslation::query()->updateOrCreate(
            ['catalog_product_id' => $primary->id, 'locale' => 'en'],
            [
                'title' => 'Seeded catalog product',
                'subtitle' => 'Reusable Laravel catalog module',
                'content' => 'Seeded translated content for the catalog product.',
            ],
        );

        CatalogProductVideo::query()->updateOrCreate(
            ['catalog_product_id' => $primary->id, 'url' => 'https://example.com/catalog-product-video'],
            [
                'title' => 'Seeded product video',
                'provider' => 'external',
                'sort_order' => 1,
            ],
        );

        CatalogStock::query()->updateOrCreate(
            ['catalog_product_id' => $primary->id, 'location' => 'Default warehouse'],
            ['quantity' => 25],
        );

        CatalogReview::query()->updateOrCreate(
            ['catalog_product_id' => $primary->id, 'author_email' => 'reviewer@example.com'],
            [
                'author_name' => 'Demo reviewer',
                'rating' => 5,
                'status' => 'published',
                'title' => 'Solid seeded product',
                'content' => 'This seeded review verifies the rebuilt review screens.',
            ],
        );

        CatalogCoupon::query()->updateOrCreate(
            ['code' => 'BASE10'],
            [
                'name' => 'Base catalog discount',
                'percentage_discount' => 10,
                'minimum_amount' => 2500,
                'starts_at' => now()->toDateString(),
                'is_active' => true,
                'usage_mode' => 'any',
            ],
        );
    }
}
