<?php

namespace Database\Seeders;

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
use Database\Seeders\Support\SeederFiles;
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
                'website_url' => 'https://example.com/base-brand',
                'intro' => 'A seeded brand profile for catalog examples.',
                'body' => 'This brand record demonstrates extended catalog brand fields without webshop behavior.',
                'meta_title' => 'Base Brand',
                'meta_description' => 'Seeded brand profile for the Laravel base CMS catalog.',
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
                'meta_description' => 'Seeded catalog product for the Laravel base CMS.',
                'brand_id' => $brand->id,
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
        $combinationSet = CatalogCombinationSet::query()->updateOrCreate(
            ['slug' => 'seeded-catalog-combination'],
            [
                'name' => 'Seeded catalog combination',
                'description' => 'A seeded combination set for catalog products.',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );
        $combinationSet->products()->sync([
            $primary->id => ['sort_order' => 1],
            $secondary->id => ['sort_order' => 2],
        ]);

        $productImagePath = SeederFiles::publicImage('seed-image-05.jpg', 'admin/uploads/catalog/images', 'seeded-catalog-product.jpg');
        $productAttachmentPath = SeederFiles::publicDocument('catalog-sheet.txt', 'admin/uploads/catalog/attachments', 'seeded-catalog-sheet.txt');

        CatalogProductImage::query()->updateOrCreate(
            ['catalog_product_id' => $primary->id, 'image_path' => $productImagePath],
            [
                'folder' => 'storage/admin/uploads/catalog/images',
                'caption' => 'Seeded catalog product',
                'sort_order' => 1,
            ],
        );

        CatalogProductAttachment::query()->updateOrCreate(
            ['catalog_product_id' => $primary->id, 'url' => $productAttachmentPath],
            [
                'name' => 'Seeded catalog sheet',
                'type' => 'text/plain',
                'sort_order' => 1,
            ],
        );

        $this->seedProductOption($primary, [
            'nl' => 'Kleur',
            'en' => 'Color',
        ], [
            ['nl' => 'Zwart', 'en' => 'Black'],
            ['nl' => 'Wit', 'en' => 'White'],
        ]);

        foreach ($this->localizedProductDetails([
            'nl' => [
                'title' => 'Voorbeeld catalogusproduct',
                'subtitle' => 'Herbruikbare Laravel catalogusmodule',
                'content' => 'Voorbeeldvertaling voor het catalogusproduct.',
            ],
            'en' => [
                'title' => 'Seeded catalog product',
                'subtitle' => 'Reusable Laravel catalog module',
                'content' => 'Seeded translated content for the catalog product.',
            ],
        ]) as $detail) {
            CatalogProductTranslation::query()->updateOrCreate(
                ['catalog_product_id' => $primary->id, 'locale' => $detail['locale']],
                [
                    'title' => $detail['title'],
                    'subtitle' => $detail['subtitle'],
                    'content' => $detail['content'],
                ],
            );
        }

        $this->seedProductOption($secondary, [
            'nl' => 'Type',
            'en' => 'Type',
        ], [
            ['nl' => 'Aanvullend product', 'en' => 'Related product'],
        ]);

        foreach ($this->localizedProductDetails([
            'nl' => [
                'title' => 'Gerelateerd voorbeeldproduct',
                'subtitle' => 'Combinatievoorbeeld',
                'content' => 'Nederlandstalige vertaling voor het gerelateerde catalogusproduct.',
            ],
            'en' => [
                'title' => 'Seeded related product',
                'subtitle' => 'Combination example',
                'content' => 'English translation for the related catalog product.',
            ],
        ]) as $detail) {
            CatalogProductTranslation::query()->updateOrCreate(
                ['catalog_product_id' => $secondary->id, 'locale' => $detail['locale']],
                [
                    'title' => $detail['title'],
                    'subtitle' => $detail['subtitle'],
                    'content' => $detail['content'],
                ],
            );
        }

        CatalogProductVideo::query()->updateOrCreate(
            ['catalog_product_id' => $primary->id, 'url' => 'https://example.com/catalog-product-video'],
            [
                'title' => 'Seeded product video',
                'provider' => 'external',
                'sort_order' => 1,
            ],
        );
    }

    /**
     * @param  array<string, array<string, string>>  $details
     * @return list<array<string, string>>
     */
    private function localizedProductDetails(array $details): array
    {
        return collect($details)
            ->map(fn (array $detail, string $locale): array => [
                'locale' => $locale,
                ...$detail,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $labelTranslations
     * @param  list<array<string, string>>  $values
     */
    private function seedProductOption(CatalogProduct $product, array $labelTranslations, array $values): void
    {
        $option = CatalogProductOption::query()->updateOrCreate(
            ['catalog_product_id' => $product->id, 'label' => $labelTranslations['en'] ?? reset($labelTranslations)],
            [
                'label_translations' => $labelTranslations,
                'sort_order' => $product->options()->max('sort_order') + 1,
            ],
        );

        foreach ($values as $index => $translations) {
            CatalogProductOptionValue::query()->updateOrCreate(
                [
                    'catalog_product_option_id' => $option->id,
                    'value' => $translations['en'] ?? reset($translations),
                ],
                [
                    'value_translations' => $translations,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
