<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaqItem extends CmsModel
{
    use SoftDeletes;

    protected $table = 'faq_items';

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(FaqCategory::class, 'faq_category_faq_item')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('faq_category_faq_item.sort_order')
            ->orderBy('faq_categories.name');
    }

    public function images(): HasMany
    {
        return $this->hasMany(FaqImage::class, 'faq_item_id')->orderBy('sort_order')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FaqAttachment::class, 'faq_item_id')->orderBy('sort_order')->orderBy('id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(FaqVideo::class, 'faq_item_id')->orderBy('sort_order')->orderBy('id');
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

        if ($this->active_from && $this->active_from->isFuture()) {
            return false;
        }

        if ($this->active_until && $this->active_until->isPast()) {
            return false;
        }

        return true;
    }
}
