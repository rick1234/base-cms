<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPart extends CmsModel
{
    protected $table = 'event_parts';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scheduleGroup(): BelongsTo
    {
        return $this->belongsTo(EventScheduleGroup::class, 'event_schedule_group_id');
    }
}
