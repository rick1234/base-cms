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

    public function defaultLocaleCode(): string
    {
        return $this->defaultLocale();
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

    public function fallbackTitle(?string $locale = null): ?string
    {
        return $this->localizedSeoValue('default_meta_title', $locale) ?: $this->default_meta_title;
    }

    public function fallbackDescription(): string
    {
        return (string) (
            $this->localizedSeoValue('default_meta_description')
            ?: $this->default_meta_description
            ?: config('cms.default_meta_description')
        );
    }

    public function fallbackOgTitle(?string $locale = null): ?string
    {
        return $this->localizedSeoValue('default_og_title', $locale) ?: $this->default_og_title;
    }

    public function fallbackOgDescription(?string $locale = null): ?string
    {
        return $this->localizedSeoValue('default_og_description', $locale) ?: $this->default_og_description;
    }

    public function fallbackOgImage(?string $locale = null): ?string
    {
        return $this->localizedSeoValue('default_og_image', $locale) ?: $this->default_og_image;
    }

    public function localizedSeoValue(string $field, ?string $locale = null): ?string
    {
        $locale = Str::of((string) ($locale ?: app()->getLocale()))
            ->replace('_', '-')
            ->lower()
            ->trim()
            ->toString();

        $value = data_get($this->settings ?? [], "seo.locales.{$locale}.{$field}");

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
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
            $path = trim($path, '/');

            return $path === '' ? url('/') : url($path);
        }

        return rtrim($this->canonical_base_url, '/').'/'.ltrim($path, '/');
    }

    /**
     * @return array<string, string>
     */
    public function localizedCanonicalUrls(string $currentUrl, string $path): array
    {
        $urls = [];

        foreach ($this->activeFrontendLocales() as $locale) {
            $localizedPath = $this->localizedPathFor($path, $locale);
            $urls[$locale] = $this->canonicalUrlFor($currentUrl, $localizedPath);
        }

        return $urls;
    }

    public function localizedPathFor(string $path, string $locale): string
    {
        $locale = Str::of($locale)->replace('_', '-')->lower()->trim()->toString();
        $segments = collect(explode('/', trim($path, '/')))
            ->filter(fn (string $segment): bool => $segment !== '')
            ->values();

        if ($segments->isNotEmpty() && $this->isFrontendLocale((string) $segments->first())) {
            $segments = $segments->slice(1)->values();
        }

        if ($locale !== $this->defaultLocale()) {
            $segments->prepend($locale);
        }

        return $segments->implode('/');
    }

    private function isFrontendLocale(string $locale): bool
    {
        $locale = Str::of($locale)->replace('_', '-')->lower()->trim()->toString();

        return in_array($locale, $this->activeFrontendLocales(), true);
    }

    public function requiresTwoFactorForBackend(): bool
    {
        return (bool) data_get($this->settings ?? [], 'security.backend_two_factor_required', false);
    }

    public static function resolveForHost(?string $host): ?self
    {
        $host = static::normalizeHost($host);

        if ($host === '') {
            return null;
        }

        return static::query()
            ->where('host', $host)
            ->orWhereHas('aliases', fn ($query) => $query->where('host', $host))
            ->first();
    }
}
