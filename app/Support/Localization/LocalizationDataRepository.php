<?php

namespace App\Support\Localization;

use App\Models\Cms\CmsLanguage;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Intl\Language\LanguageRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class LocalizationDataRepository
{
    public function __construct(
        private readonly CountryRepository $countries,
        private readonly LanguageRepository $languages,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function countries(string $locale = 'en'): Collection
    {
        return collect($this->countries->getAll($this->normalizeLocale($locale)))
            ->map(fn ($country): array => [
                'name' => $country->getName(),
                'slug' => Str::slug($country->getName()) ?: strtolower($country->getCountryCode()),
                'iso2' => $country->getCountryCode(),
                'iso3' => $country->getThreeLetterCode(),
                'numeric_code' => $country->getNumericCode(),
                'currency_code' => $country->getCurrencyCode(),
                'timezones' => $country->getTimezones(),
                'source_locale' => $country->getLocale(),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @return Collection<int, array<string, string|null>>
     */
    public function languages(string $locale = 'en'): Collection
    {
        return collect($this->languages->getAll($this->normalizeLocale($locale)))
            ->map(fn ($language): array => [
                'name' => $language->getName(),
                'slug' => Str::slug($language->getName()) ?: strtolower($language->getLanguageCode()),
                'code' => $language->getLanguageCode(),
                'native_name' => $this->nativeLanguageName($language->getLanguageCode(), $language->getName()),
                'direction' => CmsLanguage::directionFor($language->getLanguageCode()),
                'source_locale' => $language->getLocale(),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function nativeLanguageName(string $code, string $fallbackName): string
    {
        try {
            return $this->languages->get($code, $code)->getName();
        } catch (Throwable) {
            return $fallbackName;
        }
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = trim($locale) ?: 'en';

        return Str::of($locale)->replace('_', '-')->toString();
    }
}
