@extends('layouts.admin')

@php
    $usesFolderRoutes = request()->routeIs('cms.*') || request()->routeIs('admin.modules.*');
@endphp

@section('title', __('Admin Modules'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main">
            <div class="main-section">
                <div class="page-header">
                    <div class="page-header-title-container">
                        <div class="page-header-title-image-container">
                            <x-admin.material-icon class="is-large" name="extension" />
                        </div>
                        <strong>{{ __('Admin Modules') }}</strong>
                    </div>
                </div>

                @foreach ($modulesByGroup as $group => $modules)
                    <h2 class="title">{{ $groups[$group] ?? ucfirst($group) }}</h2>

                    <div class="overview-container">
                        <div class="overview-row header">
                            <div class="overview-item">{{ __('Module') }}</div>
                            <div class="overview-item">{{ __('Legacy folder') }}</div>
                            <div class="overview-item">{{ __('Table') }}</div>
                            <div class="overview-item">{{ __('Records') }}</div>
                            <div class="overview-item options">{{ __('Options') }}</div>
                        </div>

                        @foreach ($modules as $module)
                            <div class="overview-row">
                                <div class="overview-item">
                                    <a href="{{ route($routeNames['show'], $usesFolderRoutes ? $module['folder'] : $module['handle']) }}">{{ $module['name'] }}</a>
                                </div>
                                <div class="overview-item">{{ $module['folder'] }}</div>
                                <div class="overview-item">{{ $module['table'] }}</div>
                                <div class="overview-item">{{ $module['count'] }}</div>
                                <div class="overview-item options">
                                    <a href="{{ route($routeNames['show'], $usesFolderRoutes ? $module['folder'] : $module['handle']) }}" title="{{ __('Open') }}">
                                        <x-admin.material-icon name="visibility" />
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
