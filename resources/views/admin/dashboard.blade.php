@extends('layouts.admin')

@section('title', __('Dashboard'))

@section('body')
    <div class="site-wrapper-container">
        <aside class="left" aria-label="{{ __('Admin navigation') }}">
            @include('admin.partials.navigation')
        </aside>

        <main class="main">
            <section class="main-section dashboard-home-section" aria-labelledby="dashboard-title">
                <div class="dashboard-layout">
                    <section class="dashboard-module-panel" aria-labelledby="dashboard-title">
                        <h1 class="u-sr-only" id="dashboard-title">{{ __('Dashboard') }}</h1>

                        <ul class="dashboard-module-index">
                            @foreach ($dashboardModules as $module)
                                <li class="dashboard-module-index-item">
                                    <a class="dashboard-module-index-link" href="{{ $module['url'] }}" title="{{ __($module['overview_title']) }}">
                                        <span class="dashboard-module-icon dashboard-module-icon-{{ $module['theme'] }}" aria-hidden="true">
                                            <x-admin.material-icon class="is-large" :name="$module['icon']" />
                                        </span>
                                        <span class="dashboard-module-index-title">{{ __($module['title']) }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>

                    <aside class="dashboard-widget-column" aria-label="{{ __('Dashboard widgets') }}">
                        <section class="dashboard-user-widget" aria-labelledby="dashboard-user-title">
                            <div class="dashboard-user-avatar" aria-hidden="true">
                                @if ($currentUser?->image_path)
                                    <img src="{{ asset($currentUser->image_path) }}" alt="">
                                @else
                                    <span>{{ $currentUserInitials }}</span>
                                @endif
                            </div>
                            <div class="dashboard-user-content">
                                <span class="dashboard-widget-eyebrow" id="dashboard-user-title">{{ __('Signed in as') }}</span>
                                <strong>{{ $currentUser?->fullName() ?? $currentUser?->displayName() ?? __('Admin') }}</strong>
                            </div>
                            @if ($currentUserSettingsUrl)
                                <a class="dashboard-user-settings" href="{{ $currentUserSettingsUrl }}" title="{{ __('User settings') }}">
                                    <x-admin.material-icon name="manage_accounts" />
                                    <span class="u-sr-only">{{ __('User settings') }}</span>
                                </a>
                            @endif
                        </section>

                        @include('admin.dashboard.recent-list', [
                            'title' => __('Last viewed'),
                            'items' => $recentItems['viewed'],
                        ])

                        @include('admin.dashboard.recent-list', [
                            'title' => __('Last edited'),
                            'items' => $recentItems['updated'],
                        ])

                        @include('admin.dashboard.recent-list', [
                            'title' => __('Last added'),
                            'items' => $recentItems['created'],
                        ])
                    </aside>
                </div>
            </section>
        </main>
    </div>
@endsection
