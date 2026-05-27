<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventCategory extends CmsModel
{
    use SoftDeletes;

    protected $table = 'event_categories';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_hidden_from_navigation' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_category_event')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('event_category_event.sort_order')
            ->orderBy('events.title');
    }
}
