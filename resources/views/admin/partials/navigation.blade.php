@php
    $usesLegacyRoutes = request()->routeIs('cms.*');
    $homeRoute = $usesLegacyRoutes ? 'cms.index' : 'admin.dashboard';
    $navigationGroups = app(\App\Support\Admin\Dashboard\DashboardNavigationBuilder::class)->build($usesLegacyRoutes);
    $logoutLabel = session()->has('admin_impersonator_id') ? __('Return to original account') : __('Log out');
@endphp

<div class="navigation-actions-container">
    @if (($enabledLocales ?? collect())->count() > 1)
        @php
            $activeLocale = $currentLocale ?? app()->getLocale();
            $currentLanguage = $enabledLocales->firstWhere('code', $activeLocale);
            $languageNativeName = static fn ($language): string => \Illuminate\Support\Str::ucfirst((string) ($language->native_name ?: $language->name ?: strtoupper($language->code)));
            $currentLanguageLabel = $currentLanguage ? $languageNativeName($currentLanguage) : strtoupper($activeLocale);
            $modalId = 'admin-language-modal-'.str_replace(['-', '_'], '-', $activeLocale);
        @endphp

        <div class="language-widget admin-language-switcher" aria-label="{{ __('Language') }}" data-language-modal>
            <button
                class="current-language"
                type="button"
                title="{{ __('Change language') }}"
                aria-haspopup="dialog"
                aria-expanded="false"
                aria-controls="{{ $modalId }}"
                data-language-modal-trigger
            >
                <x-language-flag :locale="$activeLocale" :label="$currentLanguageLabel" decorative />
                <span class="u-sr-only">{{ __('Change language') }}: {{ $currentLanguageLabel }}</span>
            </button>

            <div class="language-modal" id="{{ $modalId }}" hidden data-language-modal-dialog>
                <div class="language-modal-backdrop" aria-hidden="true" data-language-modal-close></div>
                <section class="language-modal-panel" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
                    <header class="language-modal-header">
                        <h2 class="language-modal-title" id="{{ $modalId }}-title">{{ __('Choose language') }}</h2>
                        <button class="language-modal-close" type="button" aria-label="{{ __('Close') }}" data-language-modal-close>
                            <x-admin.material-icon name="close" />
                        </button>
                    </header>

                    <div class="language-modal-list">
                        @foreach ($enabledLocales as $language)
                            @php $languageLabel = $languageNativeName($language); @endphp
                            <form method="post" action="{{ route('locale.update', ['locale' => $language->code]) }}">
                                @csrf
                                <button
                                    class="language-modal-option {{ $activeLocale === $language->code ? 'is-active' : '' }}"
                                    type="submit"
                                    @disabled($activeLocale === $language->code)
                                >
                                    <x-language-flag :locale="$language->code" :label="$languageLabel" decorative />
                                    <span class="language-modal-option-name">{{ $languageLabel }}</span>
                                    @if ($activeLocale === $language->code)
                                        <span class="language-modal-option-status">{{ __('Current language') }}</span>
                                    @endif
                                </button>
                            </form>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    @endif
    <form method="post" action="{{ route('admin.logout') }}">
        @csrf
        <button class="logout-button" type="submit" title="{{ $logoutLabel }}">
            <x-admin.material-icon name="logout" />
            <span class="logout-button-label">{{ $logoutLabel }}</span>
        </button>
    </form>
    <button
        class="admin-sidebar-collapse-button"
        type="button"
        aria-expanded="true"
        title="{{ __('Collapse menu') }}"
        data-admin-sidebar-toggle
        data-collapse-label="{{ __('Collapse menu') }}"
        data-expand-label="{{ __('Expand menu') }}"
    >
        <x-admin.material-icon name="keyboard_double_arrow_left" />
    </button>
</div>

<div class="navigation-logo-container">
    <a href="{{ route($homeRoute) }}" title="CMS" class="logo">
        <img src="{{ asset('admin/cms/img/logo-cms-white.svg') }}" alt="CMS">
        <img src="{{ asset('admin/cms/img/logo-cms-icon.svg') }}" alt="CMS">
    </a>
</div>

<button class="btn-toggle-sidebar-button" type="button" aria-expanded="false" aria-controls="admin-navigation" data-admin-menu-button>
    <span class="btn-toggle-sidebar-button-icon">
        <x-admin.material-icon name="apps" />
    </span>
    <span class="btn-toggle-sidebar-button-label">{{ __('Menu') }}</span>
</button>

<nav id="admin-navigation" class="navigation-container" aria-label="{{ __('Admin navigation') }}" data-admin-navigation>
    <ul class="navigation-root-list">
        <li>
            <a class="navigation-root-link" href="{{ route($homeRoute) }}" title="{{ __('Home') }}" data-navigation-label="{{ __('Home') }}">
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
                        <summary class="navigation-root-link navigation-group-summary" title="{{ __($group['title']) }}" data-navigation-label="{{ __($group['title']) }}">
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
                                        <a class="navigation-submenu-link" href="{{ $link['url'] }}" title="{{ __($link['title']) }}" data-navigation-label="{{ __($link['title']) }}">
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
            {{ __('Signed in as') }}
            <span class="profile-title-name">{{ auth()->user()?->name ?? auth()->user()?->email }}</span>
        </div>
    </div>
</div>

@if (app()->environment('local'))
    <div class="db-name-container">
        <div class="db-name">
            <span class="db-name-label">{{ __('Database host') }}:</span>
            <span class="bd-name-title">{{ config('database.connections.'.config('database.default').'.host', 'local') }}</span>
        </div>
        <div class="db-name">
            <span class="db-name-label">{{ __('Database') }}:</span>
            <span class="bd-name-title">{{ config('database.connections.'.config('database.default').'.database') }}</span>
        </div>
    </div>
@endif
