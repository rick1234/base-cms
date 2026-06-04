@extends('layouts.admin')

@section('title', __('Product stock'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="catalog-stock-form" type="submit">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
                </button>
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
                    'activeTab' => 'stock',
                ])

                <form id="catalog-stock-form" method="post" action="{{ route($routeNames['stock.save'], ['id' => $product->id]) }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $product->id }}">

                    <h2 class="title">{{ __('Voorraad') }}</h2>
                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6"><strong>{{ __('Locatie') }}</strong></div>
                            <div class="col-3"><strong>{{ __('Aantal') }}</strong></div>
                            <div class="col-3"><strong>{{ __('Verwijderen') }}</strong></div>
                        </div>
                        @foreach ($product->stockRows as $index => $stock)
                            <div class="grid-row">
                                <div class="col-6">
                                    <input type="hidden" name="stock[{{ $index }}][id]" value="{{ $stock->id }}">
                                    <input name="stock[{{ $index }}][location]" type="text" value="{{ old("stock.{$index}.location", $stock->location) }}">
                                </div>
                                <div class="col-3">
                                    <input name="stock[{{ $index }}][quantity]" type="number" min="0" value="{{ old("stock.{$index}.quantity", $stock->quantity) }}">
                                </div>
                                <div class="col-3">
                                    <label>
                                        <input name="stock[{{ $index }}][delete]" type="checkbox" value="1">
                                        <span class="checkbox"></span>
                                        {{ __('Verwijderen') }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                        @for ($newIndex = 0; $newIndex < 3; $newIndex++)
                            @php $index = $product->stockRows->count() + $newIndex; @endphp
                            <div class="grid-row">
                                <div class="col-6">
                                    <input name="stock[{{ $index }}][location]" type="text" value="{{ old("stock.{$index}.location") }}">
                                </div>
                                <div class="col-3">
                                    <input name="stock[{{ $index }}][quantity]" type="number" min="0" value="{{ old("stock.{$index}.quantity") }}">
                                </div>
                                <div class="col-3"></div>
                            </div>
                        @endfor
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
