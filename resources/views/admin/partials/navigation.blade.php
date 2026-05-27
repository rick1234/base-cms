@php
    $usesLegacyRoutes = request()->routeIs('cms.*');
    $moduleRoute = $usesLegacyRoutes ? 'cms.modules.index' : 'admin.modules.show';
    $homeRoute = $usesLegacyRoutes ? 'cms.index' : 'admin.dashboard';
    $screensByGroup = collect(config('cms_modules.screens'))->groupBy('group');
    $navigationIcons = [
        'content' => 'article',
        'commerce' => 'inventory_2',
        'media' => 'panorama',
        'users' => 'group',
        'locations' => 'store',
        'events' => 'event',
        'seo' => 'route',
        'modules' => 'extension',
        'configuration' => 'settings',
        'localization' => 'translate',
        'platform' => 'extension',
        'website' => 'language',
    ];
@endphp

<div class="btn-toggle-sidebar-button">
    <div class="btn-toggle-sidebar-button-icon">
        <span class="admin-symbol admin-symbol-apps" aria-hidden="true"></span>
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
                    <span class="admin-symbol admin-symbol-dashboard" aria-hidden="true"></span>
                </span>
                <span class="navigation-item-title">{{ __('Home') }}</span>
            </a>
        </li>
    </ul>

    @foreach (config('cms_modules.groups') as $group => $label)
        @php $screens = $screensByGroup->get($group, collect()); @endphp

        @if ($screens->isNotEmpty())
            <ul class="navigation-root-list">
                <li class="navigation-root-item">
                    <details class="navigation-group">
                        <summary class="navigation-root-link navigation-group-summary">
                            <span class="navigation-item-icon">
                                <x-admin.material-icon :name="$navigationIcons[$group] ?? config('cms_icons.fallback', 'extension')" />
                            </span>
                            <span class="navigation-item-title">{{ __($label) }}</span>
                            <span class="navigation-item-chevron" aria-hidden="true">&rsaquo;</span>
                        </summary>
                        <ul class="navigation-submenu">
                            @foreach ($screens->sortBy('name') as $screen)
                                @php $path = trim(str_replace('cms/', '', $screen['legacy_path']), '/'); @endphp
                                <li>
                                    <a class="navigation-submenu-link" href="{{ route($moduleRoute, $path) }}">
                                        <span class="navigation-item-title">{{ __($screen['name']) }}</span>
                                    </a>
                                </li>
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
                        <form method="post" action="{{ route('locale.update', ['locale' => $language->code]) }}">
                            @csrf
                            <button class="admin-language-button {{ $currentLocale === $language->code ? 'is-active' : '' }}" type="submit" @disabled($currentLocale === $language->code)>
                                {{ strtoupper($language->code) }}
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
            <form method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button class="logout-button" type="submit">
                    <span class="flaticon-exit-to-app-button"></span>
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
