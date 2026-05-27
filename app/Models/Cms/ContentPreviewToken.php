<?php

namespace App\Models\Cms;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPreviewToken extends CmsModel
{
    protected $table = 'content_preview_tokens';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'used_count' => 'integer',
        ];
    }

    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class, 'content_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<ContentPreviewToken>  $query
     * @return Builder<ContentPreviewToken>
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
        });
    }

    public function allowsIp(?string $ipAddress): bool
    {
        return filled($ipAddress) && hash_equals((string) $this->ip_address, $ipAddress);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
