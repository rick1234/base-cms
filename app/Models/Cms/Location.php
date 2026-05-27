<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends CmsModel
{
    use SoftDeletes;

    protected $table = 'locations';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'metadata' => 'array',
            'active_from' => 'date',
            'active_until' => 'date',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(LocationCategory::class, 'location_category_location')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('location_category_location.sort_order')
            ->orderBy('location_categories.name');
    }

    public function images(): HasMany
    {
        return $this->hasMany(LocationImage::class, 'location_id')->orderBy('sort_order')->orderBy('id');
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(LocationOpeningHour::class, 'location_id')->orderBy('day');
    }

    public function specialOpeningHours(): HasMany
    {
        return $this->hasMany(LocationSpecialOpeningHour::class, 'location_id')->orderBy('date')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where(function (Builder $query): void {
                $query->whereNull('active_from')->orWhereDate('active_from', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('active_until')->orWhereDate('active_until', '>=', now());
            });
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->active_from && $this->active_from->isFuture()) {
            return false;
        }

        if ($this->active_until && $this->active_until->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    public static function dayNames(): array
    {
        return [
            '0' => __('Monday'),
            '1' => __('Tuesday'),
            '2' => __('Wednesday'),
            '3' => __('Thursday'),
            '4' => __('Friday'),
            '5' => __('Saturday'),
            '6' => __('Sunday'),
        ];
    }
}
