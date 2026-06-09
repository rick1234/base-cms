@props([
    'page' => null,
    'title' => null,
    'description' => null,
    'canonical' => null,
    'robots' => null,
])

@php
    $domain = $activeDomain ?? null;
    $locale = str_replace('-', '_', app()->getLocale());
    $localeCode = str_replace('_', '-', app()->getLocale());
    $firstFilled = static function (mixed ...$values): ?string {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    };
    $pageTitle = $firstFilled(
        $title,
        data_get($page, 'meta_title'),
        data_get($page, 'title'),
        data_get($page, 'name'),
        data_get($page, 'question'),
        $domain?->fallbackTitle($localeCode),
        config('app.name'),
    );
    $resolvedTitle = $domain?->seoTitle($pageTitle) ?? $pageTitle;
    $resolvedDescription = $firstFilled($description, data_get($page, 'meta_description'), $domain?->fallbackDescription(), config('cms.default_meta_description'));
    $resolvedCanonical = $firstFilled($canonical, data_get($page, 'canonical_url'), $domain?->canonicalUrlFor(url()->current(), request()->path()), url()->current());
    $resolvedRobots = $robots ?? data_get($page, 'robots') ?? $domain?->robots;
    $resolvedOgTitle = $firstFilled(data_get($page, 'og_title'), $domain?->fallbackOgTitle($localeCode), $resolvedTitle);
    $resolvedOgDescription = $firstFilled(data_get($page, 'og_description'), $domain?->fallbackOgDescription($localeCode), $resolvedDescription);
    $resolvedOgImage = $firstFilled(data_get($page, 'og_image'), $domain?->fallbackOgImage($localeCode));
    $siteName = $domain?->siteTitle() ?? config('app.name');
    $companyName = $domain?->company_name ?: $siteName;
    $themeColor = data_get($domain?->effectiveTemplateSettings() ?? [], 'primary_color', '#0f6f7a');
    $themeColor = is_string($themeColor) && preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $themeColor) === 1
        ? $themeColor
        : '#0f6f7a';
    $absoluteUrl = static function (?string $url): ?string {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        return preg_match('/^https?:\/\//i', $url) === 1 ? $url : asset(ltrim($url, '/'));
    };
    $resolvedOgImage = $absoluteUrl($resolvedOgImage);
    $resolvedCanonical = $absoluteUrl($resolvedCanonical) ?? url()->current();
    $alternateUrls = $domain?->localizedCanonicalUrls(url()->current(), request()->path()) ?? [];
    $xDefaultUrl = $alternateUrls[$domain?->defaultLocaleCode()] ?? (count($alternateUrls) > 0 ? reset($alternateUrls) : null);
    $structuredData = array_values(array_filter([
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => $domain?->canonicalUrlFor(url('/'), '') ?? url('/'),
            'inLanguage' => $localeCode,
            'description' => $resolvedDescription,
            'publisher' => [
                '@type' => 'Organization',
                'name' => $companyName,
                'url' => $domain?->canonicalUrlFor(url('/'), '') ?? url('/'),
            ],
        ],
        $companyName ? [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $companyName,
            'url' => $domain?->canonicalUrlFor(url('/'), '') ?? url('/'),
            'logo' => $absoluteUrl(data_get($domain?->effectiveTemplateSettings() ?? [], 'logo_path')),
        ] : null,
    ]));
@endphp

<title>{{ $resolvedTitle }}</title>
<meta name="description" content="{{ $resolvedDescription }}">
<meta name="author" content="{{ $companyName }}">
<meta name="theme-color" content="{{ $themeColor }}">
@if ($resolvedRobots)
    <meta name="robots" content="{{ $resolvedRobots }}">
@endif
<link rel="canonical" href="{{ $resolvedCanonical }}">
@foreach ($alternateUrls as $alternateLocale => $alternateUrl)
    <link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ $alternateUrl }}">
@endforeach
@if ($xDefaultUrl)
    <link rel="alternate" hreflang="x-default" href="{{ $xDefaultUrl }}">
@endif
<meta property="og:title" content="{{ $resolvedOgTitle }}">
<meta property="og:description" content="{{ $resolvedOgDescription }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ $locale }}">
@foreach (array_keys($alternateUrls) as $alternateLocale)
    @if (str_replace('-', '_', $alternateLocale) !== $locale)
        <meta property="og:locale:alternate" content="{{ str_replace('-', '_', $alternateLocale) }}">
    @endif
@endforeach
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $resolvedCanonical }}">
@if ($resolvedOgImage)
    <meta property="og:image" content="{{ $resolvedOgImage }}">
@endif
<meta name="twitter:card" content="{{ $resolvedOgImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $resolvedOgTitle }}">
<meta name="twitter:description" content="{{ $resolvedOgDescription }}">
@if ($resolvedOgImage)
    <meta name="twitter:image" content="{{ $resolvedOgImage }}">
@endif
@foreach ($structuredData as $schema)
    <script type="application/ld+json">@json($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
@endforeach
