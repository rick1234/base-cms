<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogProductOptionValue extends CmsModel
{
    use SoftDeletes;

    protected $table = 'catalog_product_option_values';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'value_translations' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(CatalogProductOption::class, 'catalog_product_option_id');
    }
}
