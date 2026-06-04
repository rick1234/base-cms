<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventImage extends CmsModel
{
    use SoftDeletes;

    protected $table = 'event_images';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_decorative' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
