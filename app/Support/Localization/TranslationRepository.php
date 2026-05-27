<?php

namespace App\Support\Localization;

use App\Models\Cms\CmsLanguage;
use App\Models\Cms\TranslationKey;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TranslationRepository
{
    /**
     * @return Collection<int, CmsLanguage>
     */
    public function enabledLanguages(): Collection
    {
        if (! $this->hasLanguageTable()) {
            return $this->fallbackLanguages();
        }

        try {
            $languages = CmsLanguage::query()
                ->enabled()
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        } catch (QueryException) {
            return collect();
        }

        return $languages->isNotEmpty()
            ? $languages
            : $this->fallbackLanguages();
    }

    public function defaultLocale(): string
    {
        if (! $this->hasLanguageTable()) {
            return $this->normalizeLocale(config('app.locale', 'nl'));
        }

        try {
            $locale = CmsLanguage::query()
                ->enabled()
                ->where('is_default', true)
                ->value('code');
        } catch (QueryException) {
            $locale = null;
        }

        return $this->normalizeLocale($locale ?: config('app.locale', 'nl'));
    }

    public function fallbackLocale(): string
    {
        return $this->normalizeLocale(config('app.fallback_locale', 'en'));
    }

    public function sourceLocale(): string
    {
        return $this->normalizeLocale(config('app.fallback_locale', 'en'));
    }

    public function isEnabledLocale(string $locale): bool
    {
        $locale = $this->normalizeLocale($locale);

        return $this->enabledLanguages()
            ->pluck('code')
            ->map(fn (string $code): string => $this->normalizeLocale($code))
            ->contains($locale);
    }

    public function normalizeLocale(?string $locale): string
    {
        return Str::of($locale ?: 'nl')
            ->replace('_', '-')
            ->lower()
            ->toString();
    }

    public function activeArea(): string
    {
        if (! app()->bound('request')) {
            return 'shared';
        }

        $request = request();

        if ($request->is('admin*') || $request->is('cms*')) {
            return 'admin';
        }

        $refererPath = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH);
        $refererPath = is_string($refererPath) ? trim($refererPath, '/') : '';

        return Str::is(['admin*', 'cms*'], $refererPath)
            ? 'admin'
            : 'frontend';
    }

    /**
     * @return array<string, mixed>
     */
    public function lines(string $locale, string $group, ?string $namespace): array
    {
        if ($namespace !== null && $namespace !== '*') {
            return [];
        }

        if (! $this->hasTranslationTables()) {
            return [];
        }

        $locale = $this->normalizeLocale($locale);
        $group = $group === '*' ? '*' : $group;
        $areas = ['shared', $this->activeArea()];
        $lookupLocales = collect([$locale, $this->fallbackLocale(), $this->defaultLocale()])
            ->filter()
            ->unique()
            ->values()
            ->all();

        try {
            $translationKeys = TranslationKey::query()
                ->active()
                ->whereIn('area', $areas)
                ->where('group', $group)
                ->with(['values' => fn ($query) => $query
                    ->active()
                    ->whereIn('locale', $lookupLocales)])
                ->orderByRaw("case when area = 'shared' then 0 else 1 end")
                ->orderBy('key')
                ->get();
        } catch (QueryException) {
            return [];
        }

        $lines = [];

        foreach ($translationKeys as $translationKey) {
            $value = $this->resolveValue($translationKey, $locale, $lookupLocales);

            if ($group === '*') {
                $lines[$translationKey->key] = $value;

                continue;
            }

            Arr::set($lines, $translationKey->key, $value);
        }

        return $lines;
    }

    /**
     * @param  list<string>  $lookupLocales
     */
    private function resolveValue(TranslationKey $translationKey, string $locale, array $lookupLocales): string
    {
        $value = $translationKey->valueFor($locale)?->value;

        if (filled($value)) {
            return (string) $value;
        }

        foreach ($lookupLocales as $lookupLocale) {
            $value = $translationKey->valueFor($lookupLocale)?->value;

            if (filled($value)) {
                return (string) $value;
            }
        }

        if (filled($translationKey->source_text)) {
            return (string) $translationKey->source_text;
        }

        return (string) $translationKey->key;
    }

    private function hasLanguageTable(): bool
    {
        try {
            return Schema::hasTable('languages');
        } catch (QueryException) {
            return false;
        }
    }

    /**
     * @return Collection<int, CmsLanguage>
     */
    private function fallbackLanguages(): Collection
    {
        return collect([
            new CmsLanguage([
                'name' => 'Nederlands',
                'code' => 'nl',
                'native_name' => 'Nederlands',
                'direction' => 'ltr',
                'is_enabled' => true,
                'is_default' => true,
                'status' => 'active',
            ]),
            new CmsLanguage([
                'name' => 'English',
                'code' => 'en',
                'native_name' => 'English',
                'direction' => 'ltr',
                'is_enabled' => true,
                'is_default' => false,
                'status' => 'active',
            ]),
        ]);
    }

    private function hasTranslationTables(): bool
    {
        try {
            return Schema::hasTable('translation_keys')
                && Schema::hasTable('translation_values');
        } catch (QueryException) {
            return false;
        }
    }
}
