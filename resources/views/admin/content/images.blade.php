@extends('layouts.admin')

@php
    $title = $contentItem ? __('Fotoalbum: :title', ['title' => $contentItem->title]) : __('Content images');
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <a href="{{ $contentItem ? route($routeNames['edit'], ['id' => $contentItem->id]) : $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            @include('admin.content.partials.item-tabs', [
                'active' => 'images',
                'contentItem' => $contentItem,
                'routeNames' => $routeNames,
            ])

            <div class="main-section">
                @include('admin.content.partials.page-header', [
                    'title' => $title,
                    'section' => $pageName,
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                @if (! $contentItem)
                    <div class="attachment-message">
                        <span class="flaticon-rounded-info-button"></span>
                        <em>{{ __('Sla het content item eerst op voordat u afbeeldingen toevoegt.') }}</em>
                    </div>
                @else
                    <livewire:admin.content.content-image-album :content-item="$contentItem" />
                @endif
            </div>
        </div>
    </div>
@endsection
