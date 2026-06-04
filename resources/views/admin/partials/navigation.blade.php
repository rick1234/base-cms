@php
    $usesLegacyRoutes = request()->routeIs('cms.*');
    $homeRoute = $usesLegacyRoutes ? 'cms.index' : 'admin.dashboard';
    $navigationGroups = app(\App\Support\Admin\Dashboard\DashboardNavigationBuilder::class)->build($usesLegacyRoutes);
@endphp

<div class="btn-toggle-sidebar-button">
    <div class="btn-toggle-sidebar-button-icon">
        <x-admin.material-icon name="apps" />
    </div>
    <div class="btn-toggle-sidebar-button-label">{{ __('Menu') }}</div>
</div>

<div class="navigation-logo-container">
    <a href="{{ route($homeRoute) }}" title="CMS" class="logo">
        <img src="{{ asset('admin/cms/img/logo-cms-white.svg') }}" alt="CMS">
        <img src="{{ asset('admin/cms/img/logo-cms-icon.svg') }}" alt="CMS">
    </a>
</div>

<nav class="navigation-container" aria-label="{{ __('Admin navigation') }}">
    <ul class="navigation-root-list">
        <li>
            <a class="navigation-root-link" href="{{ route($homeRoute) }}">
                <span class="navigation-item-icon">
                    <x-admin.material-icon name="home" />
                </span>
                <span class="navigation-item-title">{{ __('Home') }}</span>
            </a>
        </li>
    </ul>

    @foreach ($navigationGroups as $group)
        @if ($group['modules']->isNotEmpty())
            <ul class="navigation-root-list">
                <li class="navigation-root-item">
                    <details class="navigation-group">
                        <summary class="navigation-root-link navigation-group-summary">
                            <span class="navigation-item-icon">
                                <x-admin.material-icon :name="$group['icon']" />
                            </span>
                            <span class="navigation-item-title">{{ __($group['title']) }}</span>
                            <span class="navigation-item-chevron" aria-hidden="true">&rsaquo;</span>
                        </summary>
                        <ul class="navigation-submenu">
                            @foreach ($group['modules'] as $module)
                                @foreach ($module['links'] as $link)
                                    <li>
                                        <a class="navigation-submenu-link" href="{{ $link['url'] }}">
                                            <span class="navigation-item-icon">
                                                <x-admin.material-icon :name="$link['icon']" />
                                            </span>
                                            <span class="navigation-item-title">{{ __($link['title']) }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    </details>
                </li>
            </ul>
        @endif
    @endforeach
</nav>

<div class="navigation-user-container">
    <div class="profile-image">
        <img src="{{ asset('admin/cms/img/logo.svg') }}" alt="{{ auth()->user()?->name ?? __('Admin') }}">
    </div>
    <div class="profile-content">
        <div class="profile-title">
            {{ __('Ingelogd als: ') }}
            <span class="profile-title-name">{{ auth()->user()?->name ?? auth()->user()?->email }}</span>
        </div>
        <div class="profile-buttons-container">
            @if (($enabledLocales ?? collect())->count() > 1)
                <div class="admin-language-switcher" aria-label="{{ __('Language') }}">
                    @foreach ($enabledLocales as $language)
                        @php $languageLabel = method_exists($language, 'label') ? $language->label() : strtoupper($language->code); @endphp
                        <form method="post" action="{{ route('locale.update', ['locale' => $language->code]) }}">
                            @csrf
                            <button class="admin-language-button {{ $currentLocale === $language->code ? 'is-active' : '' }}" type="submit" title="{{ $languageLabel }}" @disabled($currentLocale === $language->code)>
                                <x-language-flag :locale="$language->code" :label="$languageLabel" decorative />
                                <span class="u-sr-only">{{ $languageLabel }}</span>
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
            <form method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button class="logout-button" type="submit">
                    <x-admin.material-icon name="logout" />
                    {{ __('Uitloggen') }}
                </button>
            </form>
        </div>
    </div>
</div>

@if (app()->environment('local'))
    <div class="db-name-container">
        <div class="db-name">
            <span class="db-name-label">Database host:</span>
            <span class="bd-name-title">{{ config('database.connections.'.config('database.default').'.host', 'local') }}</span>
        </div>
        <div class="db-name">
            <span class="db-name-label">Database:</span>
            <span class="bd-name-title">{{ config('database.connections.'.config('database.default').'.database') }}</span>
        </div>
    </div>
@endif
