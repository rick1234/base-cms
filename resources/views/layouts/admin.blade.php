<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', __('Admin')) | {{ config('app.name', 'Base CMS') }}</title>
        <meta name="robots" content="noindex,nofollow">
        <meta name="theme-color" content="#2488b8">
        <link rel="icon" href="{{ asset('admin/cms/img/favicons/favicon.svg') }}" type="image/svg+xml" sizes="any">
        <link rel="icon" href="{{ asset('admin/cms/img/favicons/favicon.ico') }}" sizes="any">
        <link rel="icon" href="{{ asset('admin/cms/img/favicons/favicon-32x32.png') }}" type="image/png" sizes="32x32">
        <link rel="icon" href="{{ asset('admin/cms/img/favicons/favicon-16x16.png') }}" type="image/png" sizes="16x16">
        <link rel="apple-touch-icon" href="{{ asset('admin/cms/img/favicons/apple-touch-icon.png') }}">
        @vite(['resources/scss/app.scss', 'resources/js/app.js'])
        @filamentStyles
        @livewireStyles
    </head>
    @php
        $adminDirtyScreen = request()->route()?->getAction('admin_screen');
        $adminDirtyScreen = is_array($adminDirtyScreen) ? end($adminDirtyScreen) : $adminDirtyScreen;
        $adminDirtySegment = request()->segment(2);
        $adminSidebarCookie = collect(explode(';', (string) request()->headers->get('cookie')))
            ->map(fn (string $cookie): array => explode('=', trim($cookie), 2))
            ->first(fn (array $cookie): bool => ($cookie[0] ?? '') === 'base_cms_admin_sidebar_collapsed');
        $adminSidebarCollapsed = rawurldecode((string) ($adminSidebarCookie[1] ?? '')) === 'true'
            || request()->cookie('base_cms_admin_sidebar_collapsed') === 'true';
        $adminDirtyModuleName = [
            'content_items' => __('pagina'),
            'content_categories' => __('paginacategorie'),
            'domains' => __('domein'),
            'website_templates' => __('template'),
            'navigation' => __('navigatiemenu'),
            'banners' => __('banner'),
            'banner_categories' => __('bannercategorie'),
            'forms' => __('formulier'),
            'form_categories' => __('formuliercategorie'),
            'catalog_products' => __('product'),
            'catalog_categories' => __('productcategorie'),
            'catalog_brands' => __('merk'),
            'catalog_promotions' => __('promotie'),
            'catalog_coupons' => __('actiecode'),
            'catalog_reviews' => __('review'),
            'faq_items' => __('FAQ'),
            'faq_categories' => __('FAQ-categorie'),
            'downloads' => __('download'),
            'download_categories' => __('downloadcategorie'),
            'vacancies' => __('vacature'),
            'vacancy_categories' => __('vacaturecategorie'),
            'countries' => $adminDirtySegment === 'talen' ? __('taal') : __('land'),
            'locations' => __('vestiging'),
            'location_categories' => __('vestigingscategorie'),
            'redirects' => __('redirect'),
            'events' => __('evenement'),
            'event_categories' => __('evenementcategorie'),
            'users' => __('gebruiker'),
            'user_categories' => __('gebruikerscategorie'),
            'roles' => __('rol'),
            'translations' => __('vertaling'),
        ][$adminDirtyScreen] ?? __('module');
    @endphp
    <body @class(['admin-sidebar-is-collapsed' => $adminSidebarCollapsed])
        data-unsaved-back-title="{{ __('Wijzigingen niet opgeslagen') }}"
        data-unsaved-back-message="{{ __('Weet u zeker dat u terug wilt gaan, er zijn wijzigingen gedaan aan deze :module') }}"
        data-unsaved-back-confirm="{{ __('Teruggaan') }}"
        data-unsaved-back-cancel="{{ __('Blijven') }}"
        data-unsaved-back-module="{{ $adminDirtyModuleName }}"
        data-delete-confirm-title="{{ __('Item verwijderen?') }}"
        data-delete-confirm-message="{{ __('Weet u zeker dat u :item wilt verwijderen?') }}"
        data-delete-confirm-button="{{ __('Verwijderen') }}"
        data-delete-confirm-cancel="{{ __('Annuleren') }}"
        data-delete-item-fallback-name="{{ __('item') }}"
    >
        <div class="admin-shell">
            @include('flash::message')
            @yield('body')
            @if (($adminBreadcrumbs ?? collect())->isNotEmpty())
                <div class="admin-breadcrumbs-bar">
                    @include('admin.partials.breadcrumbs', ['breadcrumbs' => $adminBreadcrumbs])
                </div>
            @endif
        </div>
        @livewireScripts
        @filamentScripts(withCore: true)
    </body>
</html>
