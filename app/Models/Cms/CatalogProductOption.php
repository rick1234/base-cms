<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogProductOption extends CmsModel
{
    use SoftDeletes;

    protected $table = 'catalog_product_options';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'label_translations' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CatalogProduct::class, 'catalog_product_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(CatalogProductOptionValue::class, 'catalog_product_option_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
