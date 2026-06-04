@props([
    'locale',
    'label' => null,
    'decorative' => false,
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
    $languageLabel = $label ?: strtoupper($languageCode);
@endphp

<span {{ $attributes->class('language-flag')->merge($decorative ? ['aria-hidden' => 'true'] : ['title' => $languageLabel]) }}>
    <img src="{{ $flagUrl }}" alt="{{ $decorative ? '' : $languageLabel }}">
</span>
