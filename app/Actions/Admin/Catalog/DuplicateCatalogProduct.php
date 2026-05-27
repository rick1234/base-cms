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
        $this->copyChildren($product->options, $copy);
        $this->copyChildren($product->translations, $copy);
        $this->copyChildren($product->videos, $copy);
        $this->copyChildren($product->stockRows, $copy);

        $copy->relatedProducts()->sync(
            $product->relatedProducts
                ->mapWithKeys(fn (CatalogProduct $related): array => [$related->id => ['sort_order' => $related->pivot->sort_order]])
                ->all()
        );

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
}
