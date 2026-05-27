<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CmsRedirect extends CmsModel
{
    protected $table = 'redirects';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'status_code' => 'integer',
            'is_active' => 'boolean',
            'preserve_query' => 'boolean',
            'hit_count' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function statusCodes(): array
    {
        return [
            301 => __('301 Moved Permanently'),
            302 => __('302 Found'),
            303 => __('303 See Other'),
            307 => __('307 Temporary Redirect'),
            308 => __('308 Permanent Redirect'),
        ];
    }

    /**
     * @param  Builder<CmsRedirect>  $query
     * @return Builder<CmsRedirect>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function findForPath(string $path): ?self
    {
        $sourcePath = self::normalizeSourcePath($path);
        $matches = array_values(array_unique([$sourcePath, '/'.$sourcePath]));

        return self::query()
            ->active()
            ->whereIn('source_path', $matches)
            ->first();
    }

    public static function normalizeSourcePath(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        $parsedPath = parse_url($path, PHP_URL_PATH);
        $normalized = $parsedPath !== null && $parsedPath !== false ? (string) $parsedPath : $path;

        return trim($normalized, '/');
    }

    public static function normalizeTargetUrl(?string $targetUrl): string
    {
        $targetUrl = trim((string) $targetUrl);

        if ($targetUrl === '') {
            return '';
        }

        if (Str::startsWith($targetUrl, ['http://', 'https://', '/'])) {
            return $targetUrl;
        }

        return '/'.ltrim($targetUrl, '/');
    }

    public function targetForRequest(Request $request): string
    {
        $targetUrl = $this->target_url;

        if ($this->preserve_query && $request->getQueryString()) {
            $targetUrl .= str_contains($targetUrl, '?') ? '&' : '?';
            $targetUrl .= $request->getQueryString();
        }

        return $targetUrl;
    }

    public function recordHit(): void
    {
        $this->newQuery()
            ->whereKey($this->getKey())
            ->update([
                'hit_count' => DB::raw('hit_count + 1'),
                'last_used_at' => now(),
                'updated_at' => $this->updated_at,
            ]);
    }

    public function statusLabel(): string
    {
        return self::statusCodes()[$this->status_code] ?? (string) $this->status_code;
    }
}
