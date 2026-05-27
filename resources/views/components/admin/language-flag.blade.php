@props([
    'locale',
])

@php
    $localeCode = strtolower(str_replace('_', '-', (string) $locale));
    $languageCode = str($localeCode)->before('-')->toString();
    $countryCode = match ($languageCode) {
        'en' => 'gb',
        'nl' => 'nl',
        'de' => 'de',
        'fr' => 'fr',
        'es' => 'es',
        'it' => 'it',
        'pt' => 'pt',
        'pl' => 'pl',
        'be' => 'be',
        default => $languageCode,
    };
    $flagPath = public_path("vendor/flag-icons/flags/4x3/{$countryCode}.svg");
    $flagUrl = asset(file_exists($flagPath) ? "vendor/flag-icons/flags/4x3/{$countryCode}.svg" : 'vendor/flag-icons/flags/4x3/un.svg');
@endphp

<span {{ $attributes->class('language-flag') }} title="{{ strtoupper($languageCode) }}">
    <img src="{{ $flagUrl }}" alt="{{ strtoupper($languageCode) }}">
</span>
