@extends('layouts.admin')

@section('title', __('Location images'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
                </a>
            </div>

            <div class="main-section">
                @include('admin.locations.partials.page-header', [
                    'title' => $location->name,
                    'section' => $pageName,
                ])

                @include('admin.locations.partials.tabs', [
                    'location' => $location,
                    'routeNames' => $routeNames,
                    'activeTab' => 'images',
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                <livewire:admin.locations.location-image-album :location="$location" :key="'location-image-album-'.$location->id" />
            </div>
        </div>
    </div>
@endsection
