@extends('layouts.admin')

@php
    $title = __('Fotoalbum: :title', ['title' => $fakeRecord['title']]);
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <a href="{{ route($routeNames['edit'], ['id' => $fakeRecord['id']]) }}" class="btn btn-cancel">
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
                </a>
            </div>

            @include('admin.base-module-conventions.partials.tabs', [
                'active' => 'images',
                'fakeRecord' => $fakeRecord,
                'routeNames' => $routeNames,
            ])

            <div class="main-section">
                @include('admin.content.partials.page-header', [
                    'title' => $title,
                    'section' => __('Base module conventions'),
                ])

                <span class="content-admin-screen-label">{{ __('Base module conventions') }}</span>

                <div class="base-conventions-media-grid">
                    <article>
                        <x-admin.material-icon name="image" />
                        <strong>{{ __('Hero image') }}</strong>
                        <span>{{ __('Alt text') }}: {{ __('Reference content item') }}</span>
                    </article>
                    <article>
                        <x-admin.material-icon name="photo_library" />
                        <strong>{{ __('Gallery') }}</strong>
                        <span>{{ __('Carousel ready') }}</span>
                    </article>
                </div>
            </div>
        </div>
    </div>
@endsection
