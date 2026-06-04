<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventScheduleGroup extends CmsModel
{
    protected $table = 'event_schedule_groups';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_collapsed' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(EventPart::class, 'event_schedule_group_id')
            ->orderBy('sort_order')
            ->orderBy('starts_at')
            ->orderBy('id');
    }
}
