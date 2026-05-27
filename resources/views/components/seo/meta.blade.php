@props([
    'page' => null,
    'title' => null,
    'description' => null,
    'canonical' => null,
    'robots' => null,
])

@php
    $domain = $activeDomain ?? null;
    $pageTitle = $title
        ?? data_get($page, 'meta_title')
        ?? data_get($page, 'title')
        ?? data_get($page, 'name')
        ?? data_get($page, 'question')
        ?? $domain?->default_meta_title
        ?? config('app.name');
    $resolvedTitle = $domain?->seoTitle($pageTitle) ?? $pageTitle;
    $resolvedDescription = $description
        ?? data_get($page, 'meta_description')
        ?? $domain?->fallbackDescription()
        ?? config('cms.default_meta_description');
    $resolvedCanonical = $canonical
        ?? data_get($page, 'canonical_url')
        ?? $domain?->canonicalUrlFor(url()->current(), request()->path())
        ?? url()->current();
    $resolvedRobots = $robots ?? data_get($page, 'robots') ?? $domain?->robots;
    $resolvedOgTitle = data_get($page, 'og_title') ?? $domain?->default_og_title ?? $resolvedTitle;
    $resolvedOgDescription = data_get($page, 'og_description') ?? $domain?->default_og_description ?? $resolvedDescription;
    $resolvedOgImage = data_get($page, 'og_image') ?? $domain?->default_og_image;
    $siteName = $domain?->siteTitle() ?? config('app.name');
    $companyName = $domain?->company_name ?: $siteName;
    $locale = str_replace('-', '_', app()->getLocale());
    $themeColor = data_get($domain?->effectiveTemplateSettings() ?? [], 'primary_color', '#ffa300');
    $themeColor = is_string($themeColor) && preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $themeColor) === 1
        ? $themeColor
        : '#ffa300';
@endphp

<title>{{ $resolvedTitle }}</title>
<meta name="description" content="{{ $resolvedDescription }}">
<meta name="author" content="{{ $companyName }}">
<meta name="theme-color" content="{{ $themeColor }}">
@if ($resolvedRobots)
    <meta name="robots" content="{{ $resolvedRobots }}">
@endif
<link rel="canonical" href="{{ $resolvedCanonical }}">
@if ($domain?->faviconUrl('svg'))
    <link rel="icon" type="image/svg+xml" href="{{ $domain->faviconUrl('svg') }}">
    <link rel="icon" type="image/svg+xml" sizes="16x16" href="{{ $domain->faviconUrl('icon_16') }}">
    <link rel="icon" type="image/svg+xml" sizes="32x32" href="{{ $domain->faviconUrl('icon_32') }}">
    <link rel="apple-touch-icon" href="{{ $domain->faviconUrl('apple_touch_icon') }}">
    <link rel="mask-icon" href="{{ $domain->faviconUrl('mask_icon') }}" color="{{ data_get($domain->effectiveTemplateSettings(), 'primary_color', '#165f63') }}">
    <link rel="manifest" href="{{ $domain->faviconUrl('manifest') }}">
@endif
<meta property="og:title" content="{{ $resolvedOgTitle }}">
<meta property="og:description" content="{{ $resolvedOgDescription }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ $locale }}">
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
