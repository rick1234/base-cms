@php
    $activeDomain = $activeDomain ?? null;
    $activeTemplate = $activeTemplate ?? null;
    $settings = $domainTemplateSettings ?? config('cms_domains.default_template_settings', []);
    $googleFontUrls = app(\App\Support\Domains\GoogleFontUrl::class)->stylesheetUrls($settings);
    $siteTitle = $activeDomain?->siteTitle() ?? config('app.name', 'Base CMS');
    $logoPath = trim((string) data_get($settings, 'logo_path', 'site/templates/default/assets/logo.svg'));
    $logoUrl = preg_match('/^https?:\/\//', $logoPath) === 1 ? $logoPath : asset(ltrim($logoPath, '/'));
    $stickyHeader = filter_var(data_get($settings, 'sticky_header', true), FILTER_VALIDATE_BOOLEAN);
    $stylesheetPath = trim((string) ($activeTemplate?->stylesheet_path ?? ''));
    $stylesheetUrl = null;
    $domainFaviconUrls = [
        'svg' => $activeDomain?->faviconUrl('svg'),
        'icon' => $activeDomain?->faviconUrl('icon'),
        'icon_16' => $activeDomain?->faviconUrl('icon_16'),
        'icon_32' => $activeDomain?->faviconUrl('icon_32'),
        'apple_touch_icon' => $activeDomain?->faviconUrl('apple_touch_icon'),
        'manifest' => $activeDomain?->faviconUrl('manifest'),
        'mask_icon' => $activeDomain?->faviconUrl('mask_icon'),
        'browserconfig' => $activeDomain?->faviconUrl('browserconfig'),
    ];
    $hasDomainFavicons = count(array_filter($domainFaviconUrls)) > 0;
    $faviconSvgUrl = $domainFaviconUrls['svg'] ?? ($hasDomainFavicons ? null : asset('favicon.svg'));
    $faviconIcoUrl = $domainFaviconUrls['icon'] ?? ($hasDomainFavicons ? null : asset('favicon.ico'));
    $favicon16Url = $domainFaviconUrls['icon_16'] ?? ($hasDomainFavicons ? null : asset('favicon-16x16.png'));
    $favicon32Url = $domainFaviconUrls['icon_32'] ?? ($hasDomainFavicons ? null : asset('favicon-32x32.png'));
    $appleTouchIconUrl = $domainFaviconUrls['apple_touch_icon'] ?? ($hasDomainFavicons ? null : asset('apple-touch-icon.png'));
    $manifestUrl = $domainFaviconUrls['manifest'];
    $maskIconUrl = $domainFaviconUrls['mask_icon'];
    $browserConfigUrl = $domainFaviconUrls['browserconfig'];

    if ($stylesheetPath !== '' && ! str($stylesheetPath)->startsWith('resources/')) {
        $stylesheetUrl = asset(str($stylesheetPath)->replaceStart('public/', '')->toString());
    }
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-seo.meta :page="$page ?? null" :robots="$robots ?? null" />
        @if ($faviconSvgUrl)
            <link rel="icon" href="{{ $faviconSvgUrl }}" type="image/svg+xml" sizes="any">
        @endif
        @if ($faviconIcoUrl)
            <link rel="icon" href="{{ $faviconIcoUrl }}" sizes="any">
        @endif
        @if ($favicon32Url)
            <link rel="icon" href="{{ $favicon32Url }}" type="image/png" sizes="32x32">
        @endif
        @if ($favicon16Url)
            <link rel="icon" href="{{ $favicon16Url }}" type="image/png" sizes="16x16">
        @endif
        @if ($appleTouchIconUrl)
            <link rel="apple-touch-icon" href="{{ $appleTouchIconUrl }}">
        @endif
        @if ($manifestUrl)
            <link rel="manifest" href="{{ $manifestUrl }}">
        @endif
        @if ($maskIconUrl)
            <link rel="mask-icon" href="{{ $maskIconUrl }}" color="{{ data_get($settings, 'primary_color', '#165f63') }}">
        @endif
        @if ($browserConfigUrl)
            <meta name="msapplication-config" content="{{ $browserConfigUrl }}">
        @endif
        @if (count($googleFontUrls) > 0)
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            @foreach ($googleFontUrls as $googleFontUrl)
                <link rel="stylesheet" href="{{ $googleFontUrl }}">
            @endforeach
        @endif
        @vite(['resources/scss/app.scss', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ route('frontend.domains.theme') }}">
        @if ($stylesheetUrl)
            <link rel="stylesheet" href="{{ $stylesheetUrl }}">
        @endif
    </head>
    <body @class([
        'template-default',
        'has-domain-dev-toolbar' => $isDomainPreviewMode ?? false,
    ])>
        @include('flash::message')

        @include('frontend.partials.usp-bar')

        <header @class(['header-container', 'sticky' => $stickyHeader])>
            <div class="wrapper-container">
                <a class="logo-container" href="{{ route('frontend.home') }}" title="{{ $siteTitle }}" aria-label="{{ $siteTitle }}">
                    <img class="logo-image" src="{{ $logoUrl }}" alt="{{ $siteTitle }}">
                </a>

                <div class="widgets-container">
                    <button class="btn-offscreen-navigation" type="button" aria-controls="site-offscreen-navigation" aria-expanded="false" data-site-menu-toggle>
                        <span class="site-material-icon mso" aria-hidden="true">menu</span>
                        <span class="u-sr-only">{{ __('Menu') }}</span>
                    </button>

                    @include('frontend.partials.language-switcher')

                    @if (filter_var(data_get($settings, 'search_enabled', false), FILTER_VALIDATE_BOOLEAN))
                        @include('frontend.partials.search-form')
                    @endif

                    @if (($settings['social_placement'] ?? null) === 'header')
                        @include('frontend.partials.social-links', ['links' => $activeDomain?->social_links ?? []])
                    @endif
                </div>
            </div>

            @include('frontend.partials.navigation')
        </header>

        <main class="center-container">
            @yield('content')
        </main>

        @include('frontend.partials.footer')
        @include('frontend.partials.offscreen-navigation')
        @include('frontend.partials.domain-toolbar')
    </body>
</html>
