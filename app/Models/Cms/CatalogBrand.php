<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogBrand extends CmsModel
{
    use SoftDeletes;

    protected $table = 'catalog_brands';

    public function products(): HasMany
    {
        return $this->hasMany(CatalogProduct::class, 'brand_id');
    }
}
