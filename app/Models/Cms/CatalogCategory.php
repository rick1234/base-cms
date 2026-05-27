<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogCategory extends CmsModel
{
    use SoftDeletes;

    protected $table = 'catalog_categories';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_hidden_from_navigation' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(CatalogProduct::class, 'catalog_category_product')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('catalog_category_product.sort_order')
            ->orderBy('catalog_products.name');
    }
}
