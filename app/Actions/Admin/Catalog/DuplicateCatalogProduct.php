<?php

namespace App\Actions\Admin\Catalog;

use App\Models\Cms\CatalogProduct;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

class DuplicateCatalogProduct
{
    public function handle(CatalogProduct $product, ?Authenticatable $actor = null): CatalogProduct
    {
        $copy = $product->replicate(['uuid', 'created_by', 'updated_by']);
        $copy->name = __('Copy of :name', ['name' => $product->name]);
        $copy->sku = $product->sku ? $product->sku.'-copy-'.Str::lower(Str::random(5)) : null;
        $copy->status = 'draft';
        $copy->created_by = $actor?->getAuthIdentifier();
        $copy->updated_by = $actor?->getAuthIdentifier();
        $copy->save();

        $copy->categories()->sync(
            $product->categories
                ->mapWithKeys(fn ($category): array => [$category->id => ['sort_order' => $category->pivot->sort_order]])
                ->all()
        );

        $this->copyChildren($product->images, $copy);
        $this->copyChildren($product->attachments, $copy);
        $this->copyOptions($product, $copy);
        $this->copyChildren($product->translations, $copy);
        $this->copyChildren($product->videos, $copy);
        $this->copyCombinationSetMemberships($product, $copy);

        return $copy->refresh();
    }

    /**
     * @param  iterable<int, object>  $children
     */
    private function copyChildren(iterable $children, CatalogProduct $copy): void
    {
        foreach ($children as $child) {
            $duplicate = $child->replicate(['uuid', 'created_by', 'updated_by']);
            $duplicate->catalog_product_id = $copy->id;
            $duplicate->save();
        }
    }

    private function copyOptions(CatalogProduct $product, CatalogProduct $copy): void
    {
        $product->loadMissing('options.values');

        foreach ($product->options as $option) {
            $duplicate = $option->replicate(['uuid', 'created_by', 'updated_by']);
            $duplicate->catalog_product_id = $copy->id;
            $duplicate->save();

            $this->copyOptionValues($option->values, $duplicate->id);
        }
    }

    /**
     * @param  iterable<int, object>  $values
     */
    private function copyOptionValues(iterable $values, int $optionId): void
    {
        foreach ($values as $value) {
            $duplicate = $value->replicate(['uuid', 'created_by', 'updated_by']);
            $duplicate->catalog_product_option_id = $optionId;
            $duplicate->save();
        }
    }

    private function copyCombinationSetMemberships(CatalogProduct $product, CatalogProduct $copy): void
    {
        $product->loadMissing('combinationSets');

        $copy->combinationSets()->sync(
            $product->combinationSets
                ->mapWithKeys(fn ($set): array => [$set->id => ['sort_order' => $set->pivot->sort_order]])
                ->all()
        );
    }
}
