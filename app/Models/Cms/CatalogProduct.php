<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogProduct extends CmsModel
{
    use SoftDeletes;

    protected $table = 'catalog_products';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'price' => 'integer',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CatalogCategory::class, 'catalog_category_product')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('catalog_category_product.sort_order')
            ->orderBy('catalog_categories.name');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(CatalogBrand::class, 'brand_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(CatalogProductImage::class, 'catalog_product_id')->orderBy('sort_order')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CatalogProductAttachment::class, 'catalog_product_id')->orderBy('sort_order')->orderBy('id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(CatalogProductOption::class, 'catalog_product_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CatalogProductTranslation::class, 'catalog_product_id')->orderBy('locale');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(CatalogProductVideo::class, 'catalog_product_id')->orderBy('sort_order')->orderBy('id');
    }

    public function combinationSets(): BelongsToMany
    {
        return $this->belongsToMany(CatalogCombinationSet::class, 'catalog_combination_set_products', 'catalog_product_id', 'catalog_combination_set_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('catalog_combination_set_products.sort_order')
            ->orderBy('catalog_combination_sets.name');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isActive(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        if ($this->active_from && $this->active_from->isFuture()) {
            return false;
        }

        if ($this->active_until && $this->active_until->isPast()) {
            return false;
        }

        return true;
    }

    public function priceForInput(): string
    {
        return number_format($this->price / 100, 2, '.', '');
    }
}
