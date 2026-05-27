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
