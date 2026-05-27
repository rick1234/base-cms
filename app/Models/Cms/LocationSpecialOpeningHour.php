<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationSpecialOpeningHour extends CmsModel
{
    protected $table = 'location_special_opening_hours';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'date' => 'date',
            'is_closed' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
