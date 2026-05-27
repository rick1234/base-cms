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
            'is_on_sale' => 'boolean',
            'sale_starts_at' => 'date',
            'sale_ends_at' => 'date',
            'can_be_engraved' => 'boolean',
            'price' => 'integer',
            'sale_price' => 'integer',
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

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(CatalogPromotion::class, 'promotion_id');
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
        return $this->hasMany(CatalogProductOption::class, 'catalog_product_id')->orderBy('id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CatalogProductTranslation::class, 'catalog_product_id')->orderBy('locale');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(CatalogProductVideo::class, 'catalog_product_id')->orderBy('sort_order')->orderBy('id');
    }

    public function stockRows(): HasMany
    {
        return $this->hasMany(CatalogStock::class, 'catalog_product_id')->orderBy('location')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CatalogReview::class, 'catalog_product_id')->orderByDesc('id');
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'catalog_product_combinations', 'catalog_product_id', 'related_product_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('catalog_product_combinations.sort_order')
            ->orderBy('catalog_products.name');
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

    public function salePriceForInput(): string
    {
        return $this->sale_price === null ? '' : number_format($this->sale_price / 100, 2, '.', '');
    }
}
