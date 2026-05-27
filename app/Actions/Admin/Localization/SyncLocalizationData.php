<?php

namespace App\Actions\Admin\Localization;

use App\Models\Cms\Country;
use App\Models\Cms\IsoLanguage;
use App\Models\Cms\CmsLanguage;
use App\Support\Localization\LocalizationDataRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncLocalizationData
{
    public function __construct(private readonly LocalizationDataRepository $repository) {}

    /**
     * @return array{countries:int,languages:int,iso_languages:int,default_language:string}
     */
    public function handle(?string $locale = null): array
    {
        $locale = $this->normalizeLocale($locale ?: config('app.locale', 'en'));
        $fallbackLocale = $this->normalizeLocale(config('app.fallback_locale', 'en'));

        return DB::transaction(function () use ($locale, $fallbackLocale): array {
            $countryCount = $this->syncCountries($locale);
            $languageCount = $this->syncLanguages($locale, $fallbackLocale);
            $defaultLanguage = CmsLanguage::query()->where('is_default', true)->value('code') ?: $locale;

            return [
                'countries' => $countryCount,
                'languages' => $languageCount,
                'iso_languages' => $languageCount,
                'default_language' => $defaultLanguage,
            ];
        });
    }

    private function syncCountries(string $locale): int
    {
        $count = 0;

        foreach ($this->repository->countries($locale) as $index => $definition) {
            $country = Country::query()
                ->where('iso2', $definition['iso2'])
                ->orWhere('slug', $definition['slug'])
                ->first() ?? new Country;

            $country->fill([
                'name' => $definition['name'],
                'slug' => $definition['slug'],
                'iso2' => $definition['iso2'],
                'iso3' => $definition['iso3'],
                'numeric_code' => $definition['numeric_code'],
                'currency_code' => $definition['currency_code'],
                'timezones' => $definition['timezones'],
                'source_locale' => $definition['source_locale'],
                'sort_order' => $index,
            ]);

            if (! $country->exists) {
                $country->fill([
                    'status' => 'active',
                    'is_enabled' => true,
                    'charges_vat' => false,
                ]);
            }

            $country->save();
            $count++;
        }

        return $count;
    }

    private function syncLanguages(string $locale, string $fallbackLocale): int
    {
        $existingDefaultCode = CmsLanguage::query()
            ->where('is_default', true)
            ->value('code');

        $defaultCode = $existingDefaultCode ?: $locale;
        $enabledCodes = array_values(array_unique(array_filter([$defaultCode, $fallbackLocale])));
        $count = 0;

        foreach ($this->repository->languages($locale) as $index => $definition) {
            $isoLanguage = IsoLanguage::query()
                ->where('code', $definition['code'])
                ->first() ?? new IsoLanguage;

            $isoLanguage->fill([
                'name' => $definition['name'],
                'slug' => $definition['slug'],
                'code' => $definition['code'],
                'native_name' => $definition['native_name'],
                'direction' => $definition['direction'],
                'source_locale' => $definition['source_locale'],
                'status' => 'active',
                'sort_order' => $index,
            ])->save();

            $language = CmsLanguage::query()
                ->where('code', $definition['code'])
                ->orWhere('slug', $definition['slug'])
                ->first() ?? new CmsLanguage;

            $language->fill([
                'name' => $definition['name'],
                'slug' => $definition['slug'],
                'code' => $definition['code'],
                'native_name' => $definition['native_name'],
                'direction' => $definition['direction'],
                'source_locale' => $definition['source_locale'],
                'sort_order' => $index,
            ]);

            if (! $language->exists) {
                $language->fill([
                    'status' => 'active',
                    'is_enabled' => in_array($definition['code'], $enabledCodes, true),
                    'is_default' => $definition['code'] === $defaultCode,
                ]);
            }

            $language->save();
            $count++;
        }

        $this->ensureDefaultLanguage($defaultCode, $fallbackLocale);

        return $count;
    }

    private function ensureDefaultLanguage(string $defaultCode, string $fallbackLocale): void
    {
        $defaultLanguage = CmsLanguage::query()->where('is_default', true)->first()
            ?: CmsLanguage::query()->where('code', $defaultCode)->first()
            ?: CmsLanguage::query()->where('code', $fallbackLocale)->first()
            ?: CmsLanguage::query()->orderBy('sort_order')->first();

        if (! $defaultLanguage) {
            return;
        }

        CmsLanguage::query()
            ->whereKeyNot($defaultLanguage->id)
            ->update(['is_default' => false]);

        $defaultLanguage->forceFill([
            'status' => 'active',
            'is_enabled' => true,
            'is_default' => true,
        ])->save();
    }

    private function normalizeLocale(string $locale): string
    {
        return Str::of($locale ?: 'en')->replace('_', '-')->toString();
    }
}
