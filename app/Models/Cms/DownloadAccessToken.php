<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadAccessToken extends CmsModel
{
    protected $table = 'download_access_tokens';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'used_count' => 'integer',
        ];
    }

    public function download(): BelongsTo
    {
        return $this->belongsTo(Download::class, 'download_id');
    }

    /**
     * @param  Builder<DownloadAccessToken>  $query
     * @return Builder<DownloadAccessToken>
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
