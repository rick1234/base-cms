<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaqImage extends CmsModel
{
    use SoftDeletes;

    protected $table = 'faq_images';

    public function faqItem(): BelongsTo
    {
        return $this->belongsTo(FaqItem::class, 'faq_item_id');
    }
}
