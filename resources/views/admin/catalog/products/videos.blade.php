@extends('layouts.admin')

@section('title', __('Product videos'))

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

            @include('admin.catalog.products.partials.tabs', [
                'product' => $product,
                'routeNames' => $routeNames,
                'activeTab' => 'videos',
            ])

            <div class="main-section">
                @include('admin.catalog.partials.page-header', [
                    'title' => $product->name,
                    'section' => $pageName,
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                <livewire:admin.catalog.catalog-product-video-editor :product-id="$product->id" />
            </div>
        </div>
    </div>
@endsection
