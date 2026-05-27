@extends('layouts.admin')

@php
    $moduleIcon = str($module['folder'] ?: $module['handle'])
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
                    <a class="btn" href="{{ $backUrl }}">
                        <span class="flaticon-back-arrow"></span>
                        {{ __('Terug') }}
                    </a>
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

                <p>{{ __('This legacy admin page is represented as a Laravel utility screen. Old side-effect PHP code is not executed directly.') }}</p>

                @if ($rows->count() > 0 && count($columns) > 0)
                    <div class="overview-container">
                        <div class="overview-row header">
                            @foreach ($columns as $column)
                                <div class="overview-item">{{ str($column)->replace('_', ' ')->title() }}</div>
                            @endforeach
                        </div>

                        @foreach ($rows as $row)
                            <div class="overview-row">
                                @foreach ($columns as $column)
                                    <div class="overview-item">{{ data_get($row, $column) }}</div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    {{ $rows->links('admin.partials.pagination') }}
                @endif
            </div>
        </div>
    </div>
@endsection
