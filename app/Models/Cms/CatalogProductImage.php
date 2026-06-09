<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogProductImage extends CmsModel
{
    use SoftDeletes;

    protected $table = 'catalog_product_images';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_decorative' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CatalogProduct::class, 'catalog_product_id');
    }
}
