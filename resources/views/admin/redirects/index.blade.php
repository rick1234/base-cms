@extends('layouts.admin')

@section('title', __('Redirects'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <a class="btn btn-add" href="{{ route($routeNames['create']) }}">
                    <x-admin.material-icon name="add" />
                    {{ __('Toevoegen') }}
                </a>
                @include('admin.redirects.partials.toolbar-links', ['activeSeoModule' => 'redirect'])
            </div>

            <div class="main-section">
                @include('admin.redirects.partials.page-header', [
                    'title' => __('Redirects'),
                    'section' => __('Redirect overview'),
                ])

                <span class="content-admin-screen-label">{{ __('Redirect overview') }}</span>

                <div class="overview-container redirects-overview-container">
                    <div class="overview-row header">
                        <div class="overview-item id">
                            Id
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'id', 'sorttype' => 'desc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'id', 'sorttype' => 'asc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
                        <div class="overview-item source">
                            {{ __('Van') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'source_path', 'sorttype' => 'desc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'source_path', 'sorttype' => 'asc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
                        <div class="overview-item target">{{ __('Naar') }}</div>
                        <div class="overview-item code">{{ __('Code') }}</div>
                        <div class="overview-item hits">{{ __('Hits') }}</div>
                        <div class="overview-item status">{{ __('Status') }}</div>
                        <div class="overview-item options">{{ __('Opties') }}</div>
                    </div>

                    <form method="get" action="{{ route($routeNames['index']) }}">
                        <div class="overview-row filters">
                            <div class="overview-item id">
                                <input name="id" type="text" value="{{ request('id') }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item source">
                                <input name="source_path" type="text" value="{{ request('source_path', request('old_link')) }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item target">
                                <input name="target_url" type="text" value="{{ request('target_url', request('link')) }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item code">
                                <select name="status_code">
                                    <option value="">{{ __('Alle') }}</option>
                                    @foreach ($statusCodes as $code => $label)
                                        <option value="{{ $code }}" @selected((string) request('status_code') === (string) $code)>{{ $code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="overview-item hits"></div>
                            <div class="overview-item status">
                                <select name="is_active">
                                    <option value="">{{ __('Alle') }}</option>
                                    <option value="1" @selected(request('is_active') === '1')>{{ __('Actief') }}</option>
                                    <option value="0" @selected(request('is_active') === '0')>{{ __('Inactief') }}</option>
                                </select>
                            </div>
                            <div class="overview-item options">
                                <button type="submit" title="{{ __('Zoeken') }}">
                                    <x-admin.material-icon name="search" />
                                </button>
                            </div>
                        </div>
                    </form>

                    @forelse ($redirects as $redirect)
                        <div class="overview-row">
                            <div class="overview-item id">{{ $redirect->id }}</div>
                            <div class="overview-item source">
                                <a href="{{ route($routeNames['edit'], ['id' => $redirect->id]) }}">/{{ $redirect->source_path }}</a>
                            </div>
                            <div class="overview-item target">{{ $redirect->target_url }}</div>
                            <div class="overview-item code">{{ $redirect->status_code }}</div>
                            <div class="overview-item hits">{{ $redirect->hit_count ?? 0 }}</div>
                            <div class="overview-item status">
                                <x-admin.quick-status model="redirect" :record="$redirect" :value="$redirect->is_active" :active="$redirect->is_active" />
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $redirect->id]) }}" title="{{ __('Bewerken') }}">
                                    <x-admin.material-icon name="edit" />
                                </a>
                                <form method="post" action="{{ route($routeNames['destroy'], $redirect) }}">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" title="{{ __('Verwijderen') }}">
                                        <x-admin.material-icon name="delete" />
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="overview-row">
                            <div class="overview-item source">{{ __('Geen redirects gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $redirects])
            </div>
        </div>
    </div>
@endsection
