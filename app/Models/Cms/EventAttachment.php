<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventAttachment extends CmsModel
{
    use SoftDeletes;

    protected $table = 'event_attachments';

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
