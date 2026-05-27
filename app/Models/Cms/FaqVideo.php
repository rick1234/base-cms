<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaqVideo extends CmsModel
{
    protected $table = 'faq_videos';

    public function faqItem(): BelongsTo
    {
        return $this->belongsTo(FaqItem::class, 'faq_item_id');
    }
}
