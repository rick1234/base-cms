<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaqAttachment extends CmsModel
{
    use SoftDeletes;

    protected $table = 'faq_attachments';

    public function faqItem(): BelongsTo
    {
        return $this->belongsTo(FaqItem::class, 'faq_item_id');
    }
}
