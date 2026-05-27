<?php

namespace App\Models\Cms;

class CatalogCoupon extends CmsModel
{
    protected $table = 'catalog_coupons';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
