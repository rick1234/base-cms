<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocationCategory extends CmsModel
{
    use SoftDeletes;

    protected $table = 'location_categories';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'metadata' => 'array',
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

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_category_location')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('location_category_location.sort_order')
            ->orderBy('locations.name');
    }
}
