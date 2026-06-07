<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocationImage extends CmsModel
{
    use SoftDeletes;

    protected $table = 'location_images';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_decorative' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
