<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogProductAttachment extends CmsModel
{
    use SoftDeletes;

    protected $table = 'catalog_product_attachments';

    public function product(): BelongsTo
    {
        return $this->belongsTo(CatalogProduct::class, 'catalog_product_id');
    }
}
