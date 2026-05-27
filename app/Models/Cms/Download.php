<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class Download extends CmsModel
{
    use SoftDeletes;

    protected $table = 'downloads';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'active_from' => 'date',
            'active_until' => 'date',
            'is_password_protected' => 'boolean',
            'link_expires_after_minutes' => 'integer',
            'download_count' => 'integer',
            'last_downloaded_at' => 'datetime',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(DownloadCategory::class, 'download_category_download')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('download_category_download.sort_order')
            ->orderBy('download_categories.name');
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(DownloadAccessToken::class, 'download_id');
    }

    /**
     * @param  Builder<Download>  $query
     * @return Builder<Download>
     */
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

    public function hasFile(): bool
    {
        return filled($this->file_path)
            && filled($this->file_disk)
            && Storage::disk($this->file_disk)->exists($this->file_path);
    }

    public function passwordMatches(?string $password): bool
    {
        return ! $this->is_password_protected
            || (filled($this->password_hash) && Hash::check((string) $password, $this->password_hash));
    }

    public function publicRouteKey(): string
    {
        return $this->slug ?: (string) $this->id;
    }

    public function defaultFilename(): string
    {
        return $this->original_filename ?: basename((string) $this->file_path) ?: $this->name;
    }

    public function recordDownload(): void
    {
        $this->newQuery()
            ->whereKey($this->getKey())
            ->update([
                'download_count' => DB::raw('download_count + 1'),
                'last_downloaded_at' => now(),
                'updated_at' => $this->updated_at,
            ]);
    }
}
