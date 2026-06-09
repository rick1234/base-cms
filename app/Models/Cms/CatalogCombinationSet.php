<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogCombinationSet extends CmsModel
{
    use SoftDeletes;

    protected $table = 'catalog_combination_sets';

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(CatalogProduct::class, 'catalog_combination_set_products', 'catalog_combination_set_id', 'catalog_product_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('catalog_combination_set_products.sort_order')
            ->orderBy('catalog_products.name');
    }
}
