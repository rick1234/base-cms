@extends('layouts.admin')

@section('title', __('Dashboard'))

@section('body')
    <div class="site-wrapper-container">
        <aside class="left" aria-label="{{ __('Admin navigation') }}">
            @include('admin.partials.navigation')
        </aside>

        <main class="main">
            <section class="main-section dashboard-home-section" aria-labelledby="dashboard-title">
                <h1 class="u-sr-only" id="dashboard-title">{{ __('Dashboard') }}</h1>

                <section class="admin-setup-panel" aria-labelledby="domain-setup-title">
                    <div class="admin-setup-panel-content">
                        <div class="admin-setup-panel-icon" aria-hidden="true">
                            <x-admin.material-icon name="domain_add" />
                        </div>
                        <div>
                            <h2 class="admin-setup-panel-title" id="domain-setup-title">{{ __('Website setup') }}</h2>
                            <p class="admin-setup-panel-text">{{ __('Start with a domain, connect a template, choose languages, and add SEO defaults.') }}</p>
                        </div>
                    </div>
                    <div class="admin-setup-panel-actions">
                        <a class="btn btn-add" href="{{ route('admin.domains.create') }}">
                            <span class="flaticon-add-plus-button"></span>
                            {{ __('Start domain setup') }}
                        </a>
                        <a class="btn" href="{{ route('admin.templates.create') }}">
                            <x-admin.material-icon name="dashboard_customize" />
                            {{ __('Create template') }}
                        </a>
                    </div>
                </section>

                <div class="dashboard-board">
                    @foreach ($dashboardGroups as $group)
                        <section class="dashboard-card" aria-labelledby="dashboard-group-{{ $group['key'] }}">
                            <h2 class="dashboard-card-title" id="dashboard-group-{{ $group['key'] }}">{{ __($group['title']) }}</h2>

                            <div class="dashboard-module-list">
                                @foreach ($group['modules'] as $module)
                                    @php $moduleId = 'dashboard-module-'.$group['key'].'-'.$loop->iteration; @endphp
                                    <section class="dashboard-module" aria-labelledby="{{ $moduleId }}">
                                        <div class="dashboard-module-heading">
                                            <span class="dashboard-module-icon dashboard-module-icon-{{ $module['theme'] }}" aria-hidden="true">
                                                <x-admin.material-icon class="is-large" :name="$module['icon']" />
                                            </span>
                                            <h3 class="dashboard-module-title" id="{{ $moduleId }}">{{ __($module['title']) }}</h3>
                                        </div>

                                        <ul class="dashboard-link-list">
                                            @foreach ($module['links'] as $link)
                                                <li class="dashboard-link-item">
                                                    <a class="dashboard-link" href="{{ $link['url'] }}" title="{{ __($link['title']) }}">
                                                        {{ __($link['title']) }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </section>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </section>
        </main>
    </div>
@endsection
