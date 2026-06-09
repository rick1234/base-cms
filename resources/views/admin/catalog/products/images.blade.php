@extends('layouts.admin')

@php
    $title = $product ? __('Fotoalbum: :title', ['title' => $product->name]) : __('Product images');
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <a href="{{ $product ? route($routeNames['edit'], ['id' => $product->id]) : $backUrl }}" class="btn btn-cancel">
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
                </a>
            </div>

            @include('admin.catalog.products.partials.tabs', [
                'product' => $product,
                'routeNames' => $routeNames,
                'activeTab' => 'images',
            ])

            <div class="main-section">
                @include('admin.catalog.partials.page-header', [
                    'title' => $title,
                    'section' => $pageName,
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                @if (! $product)
                    <div class="attachment-message">
                        <x-admin.material-icon name="info" />
                        <em>{{ __('Save the product before adding images.') }}</em>
                    </div>
                @else
                    <livewire:admin.catalog.catalog-product-image-album :product="$product" />
                @endif
            </div>
        </div>
    </div>
@endsection
