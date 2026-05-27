<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationOpeningHour extends CmsModel
{
    protected $table = 'location_opening_hours';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_closed' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
