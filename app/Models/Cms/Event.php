<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends CmsModel
{
    use SoftDeletes;

    protected $table = 'events';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'starts_at' => 'date',
            'ends_at' => 'date',
            'structured_blocks' => 'array',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(EventCategory::class, 'event_category_event')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('event_categories.sort_order')
            ->orderBy('event_categories.name');
    }

    public function images(): HasMany
    {
        return $this->hasMany(EventImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EventAttachment::class)->orderBy('sort_order')->orderBy('id');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(EventPart::class)->orderBy('starts_at')->orderBy('sort_order')->orderBy('id');
    }

    public function scheduleGroups(): HasMany
    {
        return $this->hasMany(EventScheduleGroup::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function isActive(): bool
    {
        return $this->status === 'published';
    }
}
