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
                                <span class="flaticon-add-plus-button"></span>
                                {{ __('Toevoegen') }}
                            </a>
                        @else
                            <a class="btn btn-add" href="{{ route($routeNames['create'], $usesFolderRoutes ? $module['folder'] : $module['handle']) }}">
                                <span class="flaticon-add-plus-button"></span>
                                {{ __('Toevoegen') }}
                            </a>
                        @endif
                    @endunless
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

                <div class="breadcrumbs">
                    <a href="{{ route('admin.dashboard') }}">{{ __('Home') }}</a> &rsaquo;
                    {{ $module['name'] }}
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
                                        <span class="flaticon-create-new-pencil-button"></span>
                                    </a>
                                    <form method="post" action="{{ route($routeNames['destroy'], [$usesFolderRoutes ? $module['folder'] : $module['handle'], $row->id]) }}">
                                        @csrf
                                        @method('delete')
                                        <button class="button-link" type="submit" title="{{ __('Delete') }}">
                                            <span class="flaticon-delete-button"></span>
                                        </button>
                                    </form>
                                </div>
                            @endunless
                        </div>
                    @empty
                        <div class="overview-row">
                            <div class="overview-item">{{ __('No records have been migrated or created for this screen yet.') }}</div>
                        </div>
                    @endforelse
                </div>

                {{ $rows->links('admin.partials.pagination') }}
            </div>
        </div>
    </div>
@endsection
