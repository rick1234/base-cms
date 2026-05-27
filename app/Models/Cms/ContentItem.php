<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentItem extends CmsModel
{
    use SoftDeletes;

    protected $table = 'content_items';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'structured_blocks' => 'array',
            'legacy_block_snapshot' => 'array',
            'legacy_blocks_migrated_at' => 'datetime',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ContentCategory::class, 'content_category_content_item')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('content_categories.sort_order')
            ->orderBy('content_categories.name');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ContentImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ContentAttachment::class)->orderBy('sort_order')->orderBy('id');
    }

    public function previewTokens(): HasMany
    {
        return $this->hasMany(ContentPreviewToken::class, 'content_item_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(ContentBlock::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isOnline(): bool
    {
        $now = now();

        return $this->status === 'published'
            && (! $this->active_from || $this->active_from->lte($now))
            && (! $this->active_until || $this->active_until->copy()->endOfDay()->gte($now));
    }

    /**
     * @param  Builder<ContentItem>  $query
     * @return Builder<ContentItem>
     */
    public function scopeOnline(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $query): void {
                $query->whereNull('active_from')
                    ->orWhereDate('active_from', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('active_until')
                    ->orWhereDate('active_until', '>=', now());
            });
    }
}
