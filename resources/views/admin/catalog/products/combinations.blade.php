@extends('layouts.admin')

@php
    $linkedIds = old('related_products', $product->relatedProducts->pluck('id')->all());
@endphp

@section('title', __('Product combinations'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="catalog-combinations-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
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
                    'activeTab' => 'combinations',
                ])

                <form id="catalog-combinations-form" method="post" action="{{ route($routeNames['combinations.save'], ['id' => $product->id]) }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $product->id }}">

                    <h2 class="title">{{ __('Combinaties') }}</h2>
                    @if ($products->isNotEmpty())
                        <div class="grid">
                            @foreach ($products as $relatedProduct)
                                <div class="grid-row">
                                    <div class="col-12">
                                        <label>
                                            <input
                                                name="related_products[]"
                                                type="checkbox"
                                                value="{{ $relatedProduct->id }}"
                                                @checked(collect($linkedIds)->contains($relatedProduct->id))
                                            >
                                            <span class="checkbox"></span>
                                            {{ $relatedProduct->name }}
                                            @if ($relatedProduct->sku)
                                                <small>{{ $relatedProduct->sku }}</small>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="attachment-message">
                            <span class="flaticon-rounded-info-button"></span>
                            <em>{{ __('Er zijn nog geen andere producten beschikbaar.') }}</em>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
@endsection
