<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogPromotion extends CmsModel
{
    use SoftDeletes;

    protected $table = 'catalog_promotions';

    public function products(): HasMany
    {
        return $this->hasMany(CatalogProduct::class, 'promotion_id');
    }
}
