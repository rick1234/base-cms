@extends('layouts.admin')

@section('title', __('WMS Modules'))

@section('body')
    <div class="admin-layout">
        @include('admin.partials.navigation')

        <main class="admin-main">
            <div class="admin-page-header">
                <div>
                    <h1>{{ __('WMS Modules') }}</h1>
                    <p>{{ __('The legacy WMS module structure translated to English Laravel modules.') }}</p>
                </div>
            </div>

            <div class="content-stack">
                @foreach ($modulesByGroup as $group => $modules)
                    <section class="admin-panel content-stack">
                        <h2>{{ $groups[$group] ?? ucfirst($group) }}</h2>
                        <div class="wms-module-grid">
                            @foreach ($modules as $module)
                                <article class="wms-module-card">
                                    <div>
                                        <h3>{{ $module['name'] }}</h3>
                                        <p>{{ $module['description'] }}</p>
                                    </div>
                                    <dl class="wms-module-card__meta">
                                        <div>
                                            <dt>{{ __('Legacy path') }}</dt>
                                            <dd>{{ $module['legacy_path'] }}</dd>
                                        </div>
                                        <div>
                                            <dt>{{ __('Laravel table') }}</dt>
                                            <dd>{{ $module['table'] }}</dd>
                                        </div>
                                        <div>
                                            <dt>{{ __('Records') }}</dt>
                                            <dd>{{ $module['count'] }}</dd>
                                        </div>
                                    </dl>
                                    <a class="button button--secondary" href="{{ route($routeNames['show'], request()->routeIs('wms.*') ? $module['folder'] : $module['handle']) }}">{{ __('Open module') }}</a>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </main>
    </div>
@endsection
