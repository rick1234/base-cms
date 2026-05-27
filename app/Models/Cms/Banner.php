<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends CmsModel
{
    use SoftDeletes;

    protected $table = 'banners';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'metadata' => 'array',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(BannerCategory::class, 'banner_category_banner')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('banner_category_banner.sort_order')
            ->orderBy('banner_categories.name');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BannerTranslation::class, 'banner_id')->orderBy('locale');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isActive(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function displayTitle(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $translation = $this->translations->firstWhere('locale', $locale) ?: $this->translations->first();

        return $translation?->title ?: $this->title ?: __('Banner #:id', ['id' => $this->id]);
    }
}
