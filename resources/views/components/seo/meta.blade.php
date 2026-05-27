@props([
    'page' => null,
    'title' => null,
    'description' => null,
    'canonical' => null,
    'robots' => null,
])

@php
    $resolvedTitle = $title ?? $page?->meta_title ?? $page?->title ?? config('app.name');
    $resolvedDescription = $description ?? $page?->meta_description ?? config('cms.default_meta_description');
    $resolvedCanonical = $canonical ?? $page?->canonical_url ?? url()->current();
    $resolvedOgTitle = $page?->og_title ?? $resolvedTitle;
    $resolvedOgDescription = $page?->og_description ?? $resolvedDescription;
@endphp

<title>{{ $resolvedTitle }}</title>
<meta name="description" content="{{ $resolvedDescription }}">
@if ($robots)
    <meta name="robots" content="{{ $robots }}">
@endif
<link rel="canonical" href="{{ $resolvedCanonical }}">
<meta property="og:title" content="{{ $resolvedOgTitle }}">
<meta property="og:description" content="{{ $resolvedOgDescription }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $resolvedCanonical }}">
