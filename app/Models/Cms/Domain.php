<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Domain extends CmsModel
{
    use SoftDeletes;

    protected $table = 'domains';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'settings' => 'array',
            'active_frontend_locales' => 'array',
            'active_backend_locales' => 'array',
            'template_settings' => 'array',
            'social_links' => 'array',
            'public_integrations' => 'array',
            'integration_credentials' => 'encrypted:array',
            'favicon_assets' => 'array',
            'is_active' => 'boolean',
            'is_development' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('host');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WebsiteTemplate::class, 'website_template_id');
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(DomainAlias::class)->orderBy('host');
    }

    public function contactForm(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'contact_form_id');
    }

    public static function normalizeHost(?string $host): string
    {
        return Str::of($host ?? '')
            ->lower()
            ->replaceStart('http://', '')
            ->replaceStart('https://', '')
            ->before('/')
            ->before(':')
            ->trim()
            ->trim('.')
            ->toString();
    }

    public static function hostWithoutWww(string $host): string
    {
        return Str::of(static::normalizeHost($host))
            ->replaceStart('www.', '')
            ->toString();
    }

    /**
     * @return array<string, mixed>
     */
    public function effectiveTemplateSettings(): array
    {
        return [
            ...config('cms_domains.default_template_settings', []),
            ...($this->template?->default_settings ?? []),
            ...($this->template_settings ?? []),
        ];
    }

    /**
     * @return list<string>
     */
    public function activeFrontendLocales(): array
    {
        return $this->configuredLocales($this->active_frontend_locales);
    }

    /**
     * @return list<string>
     */
    public function activeBackendLocales(): array
    {
        return $this->configuredLocales($this->active_backend_locales);
    }

    /**
     * @return list<string>
     */
    private function configuredLocales(mixed $locales): array
    {
        $locales = collect((array) $locales)
            ->map(fn (mixed $locale): string => Str::of((string) $locale)->replace('_', '-')->lower()->trim()->toString())
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($locales !== []) {
            return $locales;
        }

        return [$this->defaultLocale()];
    }

    private function defaultLocale(): string
    {
        return Str::of((string) ($this->default_locale ?: config('cms.default_locale', config('app.locale', 'nl'))))
            ->replace('_', '-')
            ->lower()
            ->trim()
            ->toString() ?: 'nl';
    }

    public function siteTitle(): string
    {
        return (string) ($this->name ?: config('app.name', 'Base CMS'));
    }

    public function seoTitle(?string $pageTitle = null): string
    {
        $siteTitle = $this->siteTitle();
        $pageTitle = trim((string) $pageTitle);

        if ($pageTitle === '' || $pageTitle === $siteTitle) {
            return $siteTitle;
        }

        $separator = trim((string) ($this->title_separator ?: '|'));

        return "{$pageTitle} {$separator} {$siteTitle}";
    }

    public function fallbackDescription(): string
    {
        return (string) ($this->default_meta_description ?: config('cms.default_meta_description'));
    }

    public function faviconUrl(string $key): ?string
    {
        $path = data_get($this->favicon_assets ?? [], $key);

        if (! is_string($path) || $path === '') {
            return null;
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    public function canonicalUrlFor(string $currentUrl, string $path): string
    {
        if (! $this->canonical_base_url) {
            return $currentUrl;
        }

        return rtrim($this->canonical_base_url, '/').'/'.ltrim($path, '/');
    }
}
