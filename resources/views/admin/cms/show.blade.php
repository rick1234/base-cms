@extends('layouts.admin')

@php
    $usesFolderRoutes = request()->routeIs('cms.*') || request()->routeIs('admin.modules.*');
    $moduleIcon = str($module['folder'])
        ->before('/')
        ->replace('_', '-')
        ->lower()
        ->toString();
    $moduleIcon = config("cms_icons.modules.{$moduleIcon}", config('cms_icons.fallback', 'extension'));
@endphp

@section('title', $module['name'])

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container">
                <div class="buttons-container align-right">
                    @unless ($module['read_only'] ?? false)
                        @if (request()->routeIs('cms.*') && $usesFolderRoutes && ($module['pages']['edit'] ?? false))
                            <a class="btn btn-add" href="{{ route($routeNames['edit'], [$module['folder'], 'action' => 'nw']) }}">
                                <x-admin.material-icon name="add" />
                                {{ __('Toevoegen') }}
                            </a>
                        @else
                            <a class="btn btn-add" href="{{ route($routeNames['create'], $usesFolderRoutes ? $module['folder'] : $module['handle']) }}">
                                <x-admin.material-icon name="add" />
                                {{ __('Toevoegen') }}
                            </a>
                        @endif
                    @endunless
                    @include('admin.partials.module-subitem-toolbar', ['screenKey' => $module['handle'] ?? null])
                </div>
            </div>

            <div class="main-section">
                <div class="page-header">
                    <div class="page-header-title-container">
                        <div class="page-header-title-image-container">
                            <x-admin.material-icon class="is-large" :name="$moduleIcon" />
                        </div>
                        <strong>{{ $module['name'] }}</strong>
                    </div>
                </div>

                @if ($pages->isNotEmpty())
                    <div class="tab-menu">
                        @foreach ($pages as $page)
                            <a href="{{ route($routeNames['page'], [$page['folder'], $page['page_key']]) }}">
                                {{ $page['name'] }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="overview-container">
                    <div class="overview-row header">
                        @foreach ($columns as $column)
                            <div class="overview-item">{{ str($column)->replace('_', ' ')->title() }}</div>
                        @endforeach
                        @unless ($module['read_only'] ?? false)
                            <div class="overview-item options">{{ __('Options') }}</div>
                        @endunless
                    </div>

                    @forelse ($rows as $row)
                        <div class="overview-row">
                            @foreach ($columns as $column)
                                <div class="overview-item">{{ data_get($row, $column) }}</div>
                            @endforeach

                            @unless ($module['read_only'] ?? false)
                                @php
                                    $editTarget = request()->routeIs('cms.*') && $usesFolderRoutes
                                        ? [$module['folder'], 'id' => $row->id]
                                        : [$usesFolderRoutes ? $module['folder'] : $module['handle'], $row->id];
                                @endphp
                                <div class="overview-item options">
                                    <a href="{{ route($routeNames['edit'], $editTarget) }}" title="{{ __('Edit') }}">
                                        <x-admin.material-icon name="edit" />
                                    </a>
                                    <form method="post" action="{{ route($routeNames['destroy'], [$usesFolderRoutes ? $module['folder'] : $module['handle'], $row->id]) }}">
                                        @csrf
                                        @method('delete')
                                        <button class="button-link" type="submit" title="{{ __('Delete') }}">
                                            <x-admin.material-icon name="delete" />
                                        </button>
                                    </form>
                                </div>
                            @endunless
                        </div>
                    @empty
                        <div class="overview-row">
                            <div class="overview-item">{{ $emptyStateMessage }}</div>
                        </div>
                    @endforelse
                </div>

                {{ $rows->links('admin.partials.pagination') }}
            </div>
        </div>
    </div>
@endsection
