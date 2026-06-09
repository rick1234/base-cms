<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Catalog\CatalogCombinationSetEditor;
use App\Livewire\Admin\Catalog\CatalogProductImageAlbum;
use App\Livewire\Admin\Catalog\CatalogProductOptionEditor;
use App\Livewire\Admin\Catalog\CatalogProductTranslationEditor;
use App\Livewire\Admin\Catalog\CatalogProductVideoEditor;
use App\Models\Cms\CatalogBrand;
use App\Models\Cms\CatalogCategory;
use App\Models\Cms\CatalogCombinationSet;
use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogProductAttachment;
use App\Models\Cms\CatalogProductImage;
use App\Models\Cms\CatalogProductOption;
use App\Models\Cms\CatalogProductOptionValue;
use App\Models\Cms\CatalogProductTranslation;
use App\Models\Cms\CatalogProductVideo;
use App\Models\User;
use Database\Seeders\CatalogModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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
        $this->assertSame(1, CatalogCombinationSet::query()->count());
        $this->assertSame(2, CatalogProduct::query()->count());
        $this->assertSame(1, CatalogProductImage::query()->count());
        $this->assertSame(1, CatalogProductAttachment::query()->count());
        $this->assertSame(2, CatalogProductOption::query()->count());
        $this->assertSame(3, CatalogProductOptionValue::query()->count());
        $this->assertSame(4, CatalogProductTranslation::query()->count());
        $this->assertSame(1, CatalogProductVideo::query()->count());
        $this->assertFalse(Schema::hasTable('catalog_stock'));
        $this->assertFalse(Schema::hasColumn('catalog_products', 'price_note'));
        $this->assertFalse(Schema::hasColumn('catalog_products', 'sale_price'));
        $this->assertFalse(Schema::hasColumn('catalog_products', 'can_be_engraved'));

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
        $this->assertDatabaseHas('catalog_brands', [
            'slug' => 'base-brand',
            'website_url' => 'https://example.com/base-brand',
        ]);
        $this->assertDatabaseHas('catalog_product_options', [
            'label' => 'Color',
        ]);
        $this->assertDatabaseHas('catalog_product_option_values', [
            'value' => 'Black',
        ]);
        $this->assertDatabaseHas('catalog_combination_sets', [
            'slug' => 'seeded-catalog-combination',
        ]);
        $this->assertDatabaseHas('catalog_combination_set_products', [
            'catalog_product_id' => CatalogProduct::query()->where('sku', 'BASE-001')->value('id'),
        ]);
    }

    public function test_admin_can_create_catalog_product_with_category_brand_and_attachment(): void
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
        $this->actingAs($admin)
            ->post('/admin/catalogus/edit', [
                'sku' => 'CAT-100',
                'name' => 'Modern catalog product',
                'description' => 'Product body',
                'price' => '12.99',
                'meta_title' => 'Catalog SEO title',
                'meta_description' => 'Catalog SEO description',
                'brand_id' => $brand->id,
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
            'meta_title' => 'Catalog SEO title',
            'brand_id' => $brand->id,
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

    public function test_catalog_product_utility_screens_save_media_options_translations_videos_and_combinations(): void
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
            ->get("/admin/catalogus/{$product->id}/stock")
            ->assertNotFound();

        Livewire::actingAs($admin)
            ->test(CatalogCombinationSetEditor::class)
            ->set('name', 'Utility combination')
            ->set('productIds', [$product->id, $related->id])
            ->call('save')
            ->assertSee('Combination set saved.');

        $this->assertDatabaseHas('catalog_product_images', [
            'id' => $image->id,
            'caption' => 'Renamed catalog image',
        ]);
        $this->assertDatabaseHas('catalog_product_options', [
            'catalog_product_id' => $product->id,
            'label' => 'Maat',
        ]);
        $this->assertDatabaseHas('catalog_product_option_values', [
            'value' => 'Large',
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
        $this->assertDatabaseHas('catalog_combination_sets', [
            'name' => 'Utility combination',
        ]);
        $this->assertDatabaseHas('catalog_combination_set_products', [
            'catalog_product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('catalog_combination_set_products', [
            'catalog_product_id' => $related->id,
        ]);
    }

    public function test_catalog_product_images_screen_uses_generic_album_editor(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = CatalogProduct::query()->create([
            'sku' => 'IMG-1',
            'name' => 'Image product',
            'price' => 1000,
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get("/admin/catalogus/{$product->id}/images")
            ->assertOk()
            ->assertSee('data-content-image-editor', false)
            ->assertSee('data-content-image-editor-input', false)
            ->assertSee('name="images[]"', false)
            ->assertSee('data-content-image-editor-cropper', false)
            ->assertSee('Bewerking uploaden')
            ->assertSee('Uitsnede')
            ->assertDontSee('Reeds gekoppelde fotos')
            ->assertSee(route('admin.catalog.images.upload', ['id' => $product->id]), false);

        $this->actingAs($admin)
            ->post("/admin/catalogus/{$product->id}/images", [
                'images' => [
                    UploadedFile::fake()->image('catalog-hero.jpg'),
                    UploadedFile::fake()->image('detail-shot.png'),
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('catalog_product_images', [
            'catalog_product_id' => $product->id,
            'caption' => 'Catalog Hero',
            'alt_text' => 'Catalog Hero',
            'title_text' => 'Catalog Hero',
            'original_filename' => 'catalog-hero.jpg',
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('catalog_product_images', [
            'catalog_product_id' => $product->id,
            'caption' => 'Detail Shot',
            'sort_order' => 2,
        ]);
    }

    public function test_catalog_product_option_editor_saves_translated_labels_with_nested_values(): void
    {
        $admin = User::factory()->admin()->create();
        $product = CatalogProduct::query()->create([
            'sku' => 'OPTIONS-1',
            'name' => 'Options product',
            'price' => 1000,
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get("/admin/catalogus/{$product->id}/options")
            ->assertOk()
            ->assertSee('catalog-option-editor', false)
            ->assertSee('Label toevoegen')
            ->assertSee('Optie toevoegen')
            ->assertDontSee('name="options', false);

        Livewire::actingAs($admin)
            ->test(CatalogProductOptionEditor::class, ['productId' => $product->id])
            ->set('groups', [
                [
                    'key' => 'group-color',
                    'id' => null,
                    'label' => 'Color',
                    'label_translations' => [
                        'nl' => 'Kleur',
                        'en' => 'Color',
                        'de' => '',
                        'fr' => '',
                    ],
                    'sort_order' => 1,
                    'values' => [
                        [
                            'key' => 'value-red',
                            'id' => null,
                            'value' => 'Red',
                            'value_translations' => [
                                'nl' => 'Rood',
                                'en' => 'Red',
                                'de' => '',
                                'fr' => '',
                            ],
                            'sort_order' => 1,
                        ],
                        [
                            'key' => 'value-blue',
                            'id' => null,
                            'value' => 'Blue',
                            'value_translations' => [
                                'nl' => 'Blauw',
                                'en' => 'Blue',
                                'de' => '',
                                'fr' => '',
                            ],
                            'sort_order' => 2,
                        ],
                    ],
                ],
                [
                    'key' => 'group-width',
                    'id' => null,
                    'label' => 'Width',
                    'label_translations' => [
                        'nl' => 'Breedte',
                        'en' => 'Width',
                        'de' => '',
                        'fr' => '',
                    ],
                    'sort_order' => 2,
                    'values' => [
                        [
                            'key' => 'value-120',
                            'id' => null,
                            'value' => '120 cm',
                            'value_translations' => [
                                'nl' => '120 cm',
                                'en' => '120 cm',
                                'de' => '',
                                'fr' => '',
                            ],
                            'sort_order' => 1,
                        ],
                    ],
                ],
            ])
            ->call('save')
            ->assertSee('Product options saved.');

        $color = CatalogProductOption::query()
            ->where('catalog_product_id', $product->id)
            ->where('label', 'Color')
            ->firstOrFail();

        $this->assertSame('Kleur', $color->label_translations['nl']);
        $this->assertDatabaseHas('catalog_product_option_values', [
            'catalog_product_option_id' => $color->id,
            'value' => 'Red',
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('catalog_product_option_values', [
            'catalog_product_option_id' => $color->id,
            'value' => 'Blue',
            'sort_order' => 2,
        ]);

        $red = CatalogProductOptionValue::query()
            ->where('catalog_product_option_id', $color->id)
            ->where('value', 'Red')
            ->firstOrFail();

        $this->assertSame('Rood', $red->value_translations['nl']);
    }

    public function test_catalog_product_translation_editor_saves_translated_product_content(): void
    {
        $admin = User::factory()->admin()->create();
        $product = CatalogProduct::query()->create([
            'sku' => 'TRANS-1',
            'name' => 'Translation product',
            'price' => 1000,
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get("/admin/catalogus/{$product->id}/translations")
            ->assertOk()
            ->assertSee('catalog-translation-editor', false)
            ->assertSee('Vertaling toevoegen')
            ->assertSee('Vertalingen opslaan')
            ->assertDontSee('name="translations', false);

        Livewire::actingAs($admin)
            ->test(CatalogProductTranslationEditor::class, ['productId' => $product->id])
            ->set('translations', [
                [
                    'key' => 'translation-nl',
                    'id' => null,
                    'locale' => 'nl',
                    'title' => 'Nederlandse producttitel',
                    'subtitle' => 'Nederlandse subtitel',
                    'button_text' => 'Bekijk product',
                    'link_url' => '/producten/translation-product',
                    'content' => 'Nederlandse producttekst.',
                ],
                [
                    'key' => 'translation-en',
                    'id' => null,
                    'locale' => 'en',
                    'title' => 'English product title',
                    'subtitle' => 'English subtitle',
                    'button_text' => 'View product',
                    'link_url' => '/en/products/translation-product',
                    'content' => 'English product copy.',
                ],
            ])
            ->call('save')
            ->assertSee('Product translations saved.');

        $this->assertDatabaseHas('catalog_product_translations', [
            'catalog_product_id' => $product->id,
            'locale' => 'nl',
            'title' => 'Nederlandse producttitel',
            'subtitle' => 'Nederlandse subtitel',
            'button_text' => 'Bekijk product',
            'link_url' => '/producten/translation-product',
            'content' => 'Nederlandse producttekst.',
        ]);
        $this->assertDatabaseHas('catalog_product_translations', [
            'catalog_product_id' => $product->id,
            'locale' => 'en',
            'title' => 'English product title',
            'button_text' => 'View product',
        ]);
    }

    public function test_catalog_product_video_editor_saves_and_orders_product_videos(): void
    {
        $admin = User::factory()->admin()->create();
        $product = CatalogProduct::query()->create([
            'sku' => 'VIDEO-1',
            'name' => 'Video product',
            'price' => 1000,
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get("/admin/catalogus/{$product->id}/videos")
            ->assertOk()
            ->assertSee('catalog-video-editor', false)
            ->assertSee('Video toevoegen')
            ->assertSee('Videos opslaan')
            ->assertDontSee('name="videos', false);

        Livewire::actingAs($admin)
            ->test(CatalogProductVideoEditor::class, ['productId' => $product->id])
            ->set('videos', [
                [
                    'key' => 'video-demo',
                    'id' => null,
                    'title' => 'Demo video',
                    'url' => 'https://www.youtube.com/watch?v=demo',
                    'provider' => 'youtube',
                    'sort_order' => 1,
                ],
                [
                    'key' => 'video-detail',
                    'id' => null,
                    'title' => 'Detail video',
                    'url' => 'https://vimeo.com/123456',
                    'provider' => 'vimeo',
                    'sort_order' => 2,
                ],
            ])
            ->call('moveVideoUp', 1)
            ->call('save')
            ->assertSee('Product videos saved.');

        $this->assertDatabaseHas('catalog_product_videos', [
            'catalog_product_id' => $product->id,
            'title' => 'Detail video',
            'url' => 'https://vimeo.com/123456',
            'provider' => 'vimeo',
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('catalog_product_videos', [
            'catalog_product_id' => $product->id,
            'title' => 'Demo video',
            'provider' => 'youtube',
            'sort_order' => 2,
        ]);

        $detailVideo = CatalogProductVideo::query()
            ->where('catalog_product_id', $product->id)
            ->where('title', 'Detail video')
            ->firstOrFail();

        Livewire::actingAs($admin)
            ->test(CatalogProductVideoEditor::class, ['productId' => $product->id])
            ->set('videos', [
                [
                    'key' => 'video-detail-existing',
                    'id' => $detailVideo->id,
                    'title' => 'Detail video updated',
                    'url' => 'https://vimeo.com/123456',
                    'provider' => 'vimeo',
                    'sort_order' => 1,
                ],
            ])
            ->call('save')
            ->assertSee('Product videos saved.');

        $this->assertDatabaseHas('catalog_product_videos', [
            'id' => $detailVideo->id,
            'title' => 'Detail video updated',
        ]);
        $this->assertDatabaseMissing('catalog_product_videos', [
            'catalog_product_id' => $product->id,
            'title' => 'Demo video',
        ]);
    }

    public function test_catalog_combination_set_module_saves_products_and_product_tab_lists_memberships(): void
    {
        $admin = User::factory()->admin()->create();
        $product = CatalogProduct::query()->create([
            'sku' => 'SET-1',
            'name' => 'Set product',
            'price' => 1000,
            'status' => 'published',
        ]);
        $related = CatalogProduct::query()->create([
            'sku' => 'SET-2',
            'name' => 'Related set product',
            'price' => 1200,
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get('/admin/catalogus/combinaties/create')
            ->assertOk()
            ->assertSee('catalog-combination-set-editor', false)
            ->assertSee('Combination set opslaan')
            ->assertSee('Set product')
            ->assertSee('Related set product');

        Livewire::actingAs($admin)
            ->test(CatalogCombinationSetEditor::class)
            ->set('name', 'Starter pack')
            ->set('description', 'Products that belong together.')
            ->set('productIds', [$product->id, $related->id])
            ->call('save')
            ->assertSee('Combination set saved.');

        $set = CatalogCombinationSet::query()->where('name', 'Starter pack')->firstOrFail();

        $this->assertDatabaseHas('catalog_combination_set_products', [
            'catalog_combination_set_id' => $set->id,
            'catalog_product_id' => $product->id,
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('catalog_combination_set_products', [
            'catalog_combination_set_id' => $set->id,
            'catalog_product_id' => $related->id,
            'sort_order' => 2,
        ]);

        $this->actingAs($admin)
            ->get("/admin/catalogus/{$product->id}/combinations")
            ->assertOk()
            ->assertSee('Starter pack')
            ->assertSee(route('admin.catalog.combination-sets.edit', ['id' => $set->id]), false)
            ->assertDontSee('name="related_products', false)
            ->assertDontSee('catalog-combinations-form', false);
    }

    public function test_catalog_product_image_album_livewire_edits_and_sorts_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = CatalogProduct::query()->create([
            'sku' => 'ALBUM-1',
            'name' => 'Album product',
            'price' => 1000,
            'status' => 'published',
        ]);

        Livewire::actingAs($admin)
            ->test(CatalogProductImageAlbum::class, ['product' => $product])
            ->assertSee('data-content-image-editor', false)
            ->assertSee('name="images[]"', false)
            ->set('uploads', [
                UploadedFile::fake()->image('front-view.jpg'),
                UploadedFile::fake()->image('side-view.png'),
            ])
            ->call('uploadImages')
            ->assertSee('Images uploaded.');

        $firstImage = CatalogProductImage::query()->where('catalog_product_id', $product->id)->orderBy('sort_order')->firstOrFail();
        $secondImage = CatalogProductImage::query()->where('catalog_product_id', $product->id)->orderByDesc('sort_order')->firstOrFail();

        $this->assertDatabaseHas('catalog_product_images', [
            'id' => $firstImage->id,
            'caption' => 'Front View',
            'alt_text' => 'Front View',
            'title_text' => 'Front View',
            'original_filename' => 'front-view.jpg',
        ]);

        Livewire::actingAs($admin)
            ->test(CatalogProductImageAlbum::class, ['product' => $product])
            ->call('editImage', $firstImage->id)
            ->assertSet('editingImageId', $firstImage->id)
            ->set("imageForms.{$firstImage->id}.caption", 'Catalog front')
            ->set("imageForms.{$firstImage->id}.alt_text", 'Front catalog image')
            ->set("imageForms.{$firstImage->id}.title_text", 'Front view')
            ->set("imageForms.{$firstImage->id}.description", 'A product image for catalog SEO.')
            ->set("imageForms.{$firstImage->id}.credit", 'Studio')
            ->call('saveImage', $firstImage->id)
            ->assertSet('editingImageId', null)
            ->call('moveImage', $firstImage->id, $secondImage->id, 'before')
            ->assertSee('Image order saved.');

        $this->assertDatabaseHas('catalog_product_images', [
            'id' => $firstImage->id,
            'caption' => 'Catalog front',
            'alt_text' => 'Front catalog image',
            'title_text' => 'Front view',
            'description' => 'A product image for catalog SEO.',
            'credit' => 'Studio',
            'sort_order' => 2,
        ]);
        $this->assertDatabaseHas('catalog_product_images', [
            'id' => $secondImage->id,
            'sort_order' => 1,
        ]);
    }

    public function test_catalog_support_tables_can_be_managed_with_legacy_paths(): void
    {
        $admin = User::factory()->admin()->create();

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
                'website_url' => 'https://example.com/legacy-brand',
                'intro' => 'Brand intro',
                'body' => 'Brand content',
                'meta_title' => 'Legacy brand meta title',
                'meta_description' => 'Legacy brand meta description',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/catalogus/merken/1/edit');

        $this->assertDatabaseHas('catalog_categories', [
            'name' => 'Legacy catalog category',
            'description' => 'Category content',
        ]);
        $this->assertDatabaseHas('catalog_brands', [
            'name' => 'Legacy catalog brand',
            'website_url' => 'https://example.com/legacy-brand',
            'intro' => 'Brand intro',
            'body' => 'Brand content',
            'meta_title' => 'Legacy brand meta title',
            'meta_description' => 'Legacy brand meta description',
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
        $combinationSet = CatalogCombinationSet::query()->create([
            'name' => 'Duplicated set',
            'slug' => 'duplicated-set',
            'status' => 'active',
        ]);
        $combinationSet->products()->sync([
            $product->id => ['sort_order' => 1],
            $related->id => ['sort_order' => 2],
        ]);

        $option = CatalogProductOption::query()->create([
            'catalog_product_id' => $product->id,
            'label' => 'Format',
            'label_translations' => ['nl' => 'Formaat', 'en' => 'Format'],
            'sort_order' => 1,
        ]);
        CatalogProductOptionValue::query()->create([
            'catalog_product_option_id' => $option->id,
            'value' => 'Box',
            'value_translations' => ['nl' => 'Doos', 'en' => 'Box'],
            'sort_order' => 1,
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
        $copiedOption = CatalogProductOption::query()
            ->where('catalog_product_id', $copy->id)
            ->where('label', 'Format')
            ->firstOrFail();
        $this->assertDatabaseHas('catalog_product_option_values', [
            'catalog_product_option_id' => $copiedOption->id,
            'value' => 'Box',
        ]);
        $this->assertDatabaseHas('catalog_combination_set_products', [
            'catalog_product_id' => $copy->id,
            'catalog_combination_set_id' => $combinationSet->id,
        ]);
    }

    public function test_catalog_product_seo_tab_saves_without_clearing_product_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $category = CatalogCategory::query()->create([
            'name' => 'SEO category',
            'slug' => 'seo-category',
            'status' => 'active',
        ]);
        $product = CatalogProduct::query()->create([
            'sku' => 'SEO-1',
            'name' => 'SEO catalog product',
            'description' => 'Existing product body',
            'price' => 4321,
            'status' => 'published',
            'active_from' => '2026-05-01',
        ]);
        $product->categories()->sync([$category->id => ['sort_order' => 1]]);

        $this->actingAs($admin)
            ->get("/admin/catalogus/{$product->id}/edit/seo")
            ->assertOk()
            ->assertSee('name="meta_title"', false)
            ->assertDontSee('name="price_note"', false)
            ->assertDontSee('name="sale_price"', false)
            ->assertDontSee('name="can_be_engraved"', false);

        $this->actingAs($admin)
            ->post("/admin/catalogus/{$product->id}", [
                'id' => $product->id,
                'active_tab' => 'seo',
                'meta_title' => 'SEO product title',
                'meta_description' => 'SEO product description',
            ])
            ->assertRedirect("/admin/catalogus/{$product->id}/edit/seo");

        $product->refresh();

        $this->assertSame('SEO catalog product', $product->name);
        $this->assertSame(4321, $product->price);
        $this->assertSame('SEO product title', $product->meta_title);
        $this->assertSame('SEO product description', $product->meta_description);
        $this->assertDatabaseHas('catalog_category_product', [
            'catalog_category_id' => $category->id,
            'catalog_product_id' => $product->id,
        ]);
    }
}
