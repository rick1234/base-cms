<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventImage extends CmsModel
{
    use SoftDeletes;

    protected $table = 'event_images';

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
