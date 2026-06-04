<?php

namespace Tests\Feature\Admin;

use App\Models\User;
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
use Database\Seeders\CatalogModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_module_seeder_creates_demo_catalog_without_duplicates(): void
    {
        $this->seed(CatalogModuleSeeder::class);
        $this->seed(CatalogModuleSeeder::class);

        $this->assertSame(2, CatalogCategory::query()->count());
        $this->assertSame(1, CatalogBrand::query()->count());
        $this->assertSame(1, CatalogPromotion::query()->count());
        $this->assertSame(2, CatalogProduct::query()->count());
        $this->assertSame(1, CatalogProductImage::query()->count());
        $this->assertSame(1, CatalogProductAttachment::query()->count());
        $this->assertSame(4, CatalogProductOption::query()->count());
        $this->assertSame(4, CatalogProductTranslation::query()->count());
        $this->assertSame(1, CatalogProductVideo::query()->count());
        $this->assertSame(1, CatalogStock::query()->count());
        $this->assertSame(1, CatalogReview::query()->count());
        $this->assertSame(1, CatalogCoupon::query()->count());

        $this->assertDatabaseHas('catalog_products', [
            'sku' => 'BASE-001',
            'name' => 'Seeded catalog product',
        ]);
        $this->assertDatabaseHas('catalog_product_translations', [
            'locale' => 'nl',
            'title' => 'Voorbeeld catalogusproduct',
        ]);
        $this->assertDatabaseHas('catalog_product_translations', [
            'locale' => 'en',
            'title' => 'Seeded catalog product',
        ]);
        $this->assertDatabaseHas('catalog_coupons', [
            'code' => 'BASE10',
        ]);
    }

    public function test_admin_can_create_catalog_product_with_category_brand_promotion_and_attachment(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $category = CatalogCategory::query()->create([
            'name' => 'Webshop',
            'slug' => 'webshop',
            'status' => 'active',
        ]);
        $brand = CatalogBrand::query()->create([
            'name' => 'Laravel Brand',
            'slug' => 'laravel-brand',
            'status' => 'active',
        ]);
        $promotion = CatalogPromotion::query()->create([
            'name' => 'Spring deal',
            'slug' => 'spring-deal',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/catalogus/edit', [
                'sku' => 'CAT-100',
                'name' => 'Modern catalog product',
                'description' => 'Product body',
                'price' => '12.99',
                'price_note' => 'Includes VAT',
                'is_on_sale' => 1,
                'sale_price' => '9.99',
                'meta_description' => 'Catalog SEO description',
                'brand_id' => $brand->id,
                'promotion_id' => $promotion->id,
                'status' => 'published',
                'active_from' => '2026-05-01',
                'categories' => [$category->id],
                'attachment_files' => [
                    UploadedFile::fake()->create('product-sheet.pdf', 12, 'application/pdf'),
                ],
            ])
            ->assertRedirect('/admin/catalogus/1/edit');

        $this->assertDatabaseHas('catalog_products', [
            'sku' => 'CAT-100',
            'name' => 'Modern catalog product',
            'price' => 1299,
            'sale_price' => 999,
            'brand_id' => $brand->id,
            'promotion_id' => $promotion->id,
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('catalog_category_product', [
            'catalog_category_id' => $category->id,
            'catalog_product_id' => 1,
        ]);
        $this->assertDatabaseHas('catalog_product_attachments', [
            'catalog_product_id' => 1,
            'name' => 'product-sheet.pdf',
        ]);

        $this->actingAs($admin)
            ->get('/admin/catalogus')
            ->assertOk()
            ->assertSee('Modern catalog product')
            ->assertSee('Webshop')
            ->assertDontSee('ajax/duplicateItem', false)
            ->assertDontSee('Dupliceren');
    }

    public function test_catalog_product_utility_screens_save_media_options_translations_videos_stock_and_combinations(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = CatalogProduct::query()->create([
            'sku' => 'UTILITY-1',
            'name' => 'Utility product',
            'price' => 1000,
            'status' => 'published',
        ]);
        $related = CatalogProduct::query()->create([
            'sku' => 'UTILITY-2',
            'name' => 'Related product',
            'price' => 1500,
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post("/admin/catalogus/ajax/uploadAfbeelding?id={$product->id}", [
                'image' => UploadedFile::fake()->image('catalog.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $image = CatalogProductImage::query()->firstOrFail();

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/catalogus/ajax/updateAfbeeldingnaam', [
                'uploadId' => $image->id,
                'uploadName' => 'Renamed catalog image',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->actingAs($admin)
            ->post('/admin/catalogus/editOptions', [
                'id' => $product->id,
                'options' => [
                    ['locale' => 'nl', 'label' => 'Maat', 'value' => 'Large'],
                ],
            ])
            ->assertRedirect("/admin/catalogus/{$product->id}/options");

        $this->actingAs($admin)
            ->post('/admin/catalogus/editVertalingen', [
                'id' => $product->id,
                'translations' => [
                    ['locale' => 'en', 'title' => 'Utility product EN', 'content' => 'Translated content'],
                ],
            ])
            ->assertRedirect("/admin/catalogus/{$product->id}/translations");

        $this->actingAs($admin)
            ->post('/admin/catalogus/editVideo', [
                'id' => $product->id,
                'videos' => [
                    ['title' => 'Product video', 'url' => 'https://example.com/product-video', 'provider' => 'external'],
                ],
            ])
            ->assertRedirect("/admin/catalogus/{$product->id}/videos");

        $this->actingAs($admin)
            ->post('/admin/catalogus/editVoorraad', [
                'id' => $product->id,
                'stock' => [
                    ['location' => 'Main warehouse', 'quantity' => 8],
                ],
            ])
            ->assertRedirect("/admin/catalogus/{$product->id}/stock");

        $this->actingAs($admin)
            ->post('/admin/catalogus/editCombinaties', [
                'id' => $product->id,
                'related_products' => [$related->id],
            ])
            ->assertRedirect("/admin/catalogus/{$product->id}/combinations");

        $this->assertDatabaseHas('catalog_product_images', [
            'id' => $image->id,
            'caption' => 'Renamed catalog image',
        ]);
        $this->assertDatabaseHas('catalog_product_options', [
            'catalog_product_id' => $product->id,
            'label' => 'Maat',
        ]);
        $this->assertDatabaseHas('catalog_product_translations', [
            'catalog_product_id' => $product->id,
            'locale' => 'en',
            'title' => 'Utility product EN',
        ]);
        $this->assertDatabaseHas('catalog_product_videos', [
            'catalog_product_id' => $product->id,
            'title' => 'Product video',
        ]);
        $this->assertDatabaseHas('catalog_stock', [
            'catalog_product_id' => $product->id,
            'location' => 'Main warehouse',
            'quantity' => 8,
        ]);
        $this->assertDatabaseHas('catalog_product_combinations', [
            'catalog_product_id' => $product->id,
            'related_product_id' => $related->id,
        ]);
    }

    public function test_catalog_support_tables_can_be_managed_with_legacy_paths(): void
    {
        $admin = User::factory()->admin()->create();
        $product = CatalogProduct::query()->create([
            'sku' => 'REVIEW-1',
            'name' => 'Reviewed product',
            'price' => 1000,
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->post('/admin/catalogus/categorieen/edit', [
                'naam' => 'Legacy catalog category',
                'slug' => 'legacy-catalog-category',
                'content' => 'Category content',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/catalogus/categorieen/1/edit');

        $this->actingAs($admin)
            ->post('/admin/catalogus/merken/edit', [
                'naam' => 'Legacy catalog brand',
                'slug' => 'legacy-catalog-brand',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/catalogus/merken/1/edit');

        $this->actingAs($admin)
            ->post('/admin/catalogus/promotie/edit', [
                'titel' => 'Legacy catalog promotion',
                'slug' => 'legacy-catalog-promotion',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/catalogus/promotie/1/edit');

        $this->actingAs($admin)
            ->post('/admin/catalogus/actiecodes/edit', [
                'naam' => 'Legacy action code',
                'code' => 'LEGACY10',
                'kortingspercentage' => 10,
                'minimum_bedrag' => '25.00',
                'is_active' => 1,
                'usage_mode' => 'any',
            ])
            ->assertRedirect('/admin/catalogus/actiecodes/1/edit');

        $this->actingAs($admin)
            ->post('/admin/catalogus/review/edit', [
                'catalog_product_id' => $product->id,
                'author_name' => 'Reviewer',
                'author_email' => 'reviewer@example.com',
                'rating' => 4,
                'status' => 'published',
                'title' => 'Good product',
                'content' => 'Solid catalog review.',
            ])
            ->assertRedirect('/admin/catalogus/review/1/edit');

        $this->assertDatabaseHas('catalog_categories', [
            'name' => 'Legacy catalog category',
            'description' => 'Category content',
        ]);
        $this->assertDatabaseHas('catalog_brands', [
            'name' => 'Legacy catalog brand',
        ]);
        $this->assertDatabaseHas('catalog_promotions', [
            'name' => 'Legacy catalog promotion',
        ]);
        $this->assertDatabaseHas('catalog_coupons', [
            'code' => 'LEGACY10',
            'minimum_amount' => 2500,
        ]);
        $this->assertDatabaseHas('catalog_reviews', [
            'catalog_product_id' => $product->id,
            'title' => 'Good product',
            'status' => 'published',
        ]);
    }

    public function test_catalog_products_can_be_duplicated_with_related_records(): void
    {
        $admin = User::factory()->admin()->create();
        $category = CatalogCategory::query()->create([
            'name' => 'Duplicated category',
            'slug' => 'duplicated-category',
            'status' => 'active',
        ]);
        $product = CatalogProduct::query()->create([
            'sku' => 'DUP-1',
            'name' => 'Original catalog product',
            'price' => 2000,
            'status' => 'published',
        ]);
        $related = CatalogProduct::query()->create([
            'sku' => 'DUP-2',
            'name' => 'Related duplicated product',
            'price' => 2000,
            'status' => 'published',
        ]);
        $product->categories()->sync([$category->id => ['sort_order' => 1]]);
        $product->relatedProducts()->sync([$related->id => ['sort_order' => 1]]);

        CatalogProductOption::query()->create([
            'catalog_product_id' => $product->id,
            'locale' => 'nl',
            'label' => 'Format',
            'value' => 'Box',
        ]);
        CatalogStock::query()->create([
            'catalog_product_id' => $product->id,
            'location' => 'Warehouse',
            'quantity' => 3,
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/catalogus/ajax/duplicateItem', [
                'itemId' => $product->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $copy = CatalogProduct::query()
            ->where('sku', 'like', 'DUP-1-copy-%')
            ->firstOrFail();

        $this->assertSame('draft', $copy->status);
        $this->assertDatabaseHas('catalog_category_product', [
            'catalog_category_id' => $category->id,
            'catalog_product_id' => $copy->id,
        ]);
        $this->assertDatabaseHas('catalog_product_options', [
            'catalog_product_id' => $copy->id,
            'label' => 'Format',
        ]);
        $this->assertDatabaseHas('catalog_stock', [
            'catalog_product_id' => $copy->id,
            'location' => 'Warehouse',
        ]);
        $this->assertDatabaseHas('catalog_product_combinations', [
            'catalog_product_id' => $copy->id,
            'related_product_id' => $related->id,
        ]);
    }
}
