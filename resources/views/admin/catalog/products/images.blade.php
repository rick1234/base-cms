@extends('layouts.admin')

@section('title', __('Product images'))

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
                @include('admin.catalog.partials.page-header', [
                    'title' => $product->name,
                    'section' => $pageName,
                ])

                @include('admin.catalog.products.partials.tabs', [
                    'product' => $product,
                    'routeNames' => $routeNames,
                    'activeTab' => 'images',
                ])

                <h2 class="title">{{ __('Afbeeldingen') }}</h2>
                <form enctype="multipart/form-data" method="post" action="{{ route($routeNames['image.upload'], ['id' => $product->id]) }}">
                    @csrf
                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="catalog_image">{{ __('Afbeelding') }}</label>
                        </div>
                        <div class="form-item-input">
                            <input id="catalog_image" name="image" type="file" accept="image/*" required>
                            <input name="caption" type="text" placeholder="{{ __('Titel') }}">
                            <button class="btn btn-add" type="submit">
                                <x-admin.material-icon name="add" />
                                {{ __('Uploaden') }}
                            </button>
                        </div>
                    </div>
                </form>

                <h3 class="sub-title">{{ __('Reeds gekoppelde fotos') }}</h3>
                @include('admin.content.partials.media-items', [
                    'images' => $product->images,
                    'deleteRoute' => $routeNames['image.delete'],
                    'updateNameRoute' => $routeNames['image.update-name'],
                ])
            </div>
        </div>
    </div>
@endsection
