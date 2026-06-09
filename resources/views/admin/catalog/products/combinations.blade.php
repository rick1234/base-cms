@extends('layouts.admin')

@section('title', __('Product combinations'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <a class="btn btn-add" href="{{ route($routeNames['combination-sets.create']) }}">
                    <x-admin.material-icon name="add" />
                    {{ __('Combination set toevoegen') }}
                </a>
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
                    'activeTab' => 'combinations',
                ])

                <div class="catalog-product-combination-memberships">
                    <div class="response-mail-builder-toolbar catalog-combination-set-toolbar">
                        <div class="response-mail-builder-title">
                            <h2 class="title">{{ __('Combinaties') }}</h2>
                            <span>{{ trans_choice('{0} Geen sets|{1} :count set|[2,*] :count sets', $product->combinationSets->count(), ['count' => $product->combinationSets->count()]) }}</span>
                        </div>
                    </div>

                    <div class="response-mail-list catalog-combination-set-list">
                        @forelse ($product->combinationSets as $set)
                            <article class="response-mail-card catalog-combination-set-card">
                                <header class="response-mail-card-header">
                                    <div>
                                        <span class="response-mail-card-kicker">{{ __('Set') }}</span>
                                        <h3>{{ $set->name }}</h3>
                                    </div>
                                    <div class="response-mail-card-actions">
                                        <a class="btn btn-icon-only" href="{{ route($routeNames['combination-sets.edit'], ['id' => $set->id]) }}" title="{{ __('Bewerken') }}">
                                            <x-admin.material-icon name="edit" />
                                        </a>
                                    </div>
                                </header>

                                @if ($set->description)
                                    <p class="catalog-combination-set-description">{{ $set->description }}</p>
                                @endif

                                <div class="catalog-combination-membership-products">
                                    @foreach ($set->products as $setProduct)
                                        <span @class(['is-current' => $setProduct->id === $product->id])>
                                            {{ $setProduct->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </article>
                        @empty
                            <div class="attachment-message">
                                <x-admin.material-icon name="info" />
                                <em>{{ __('Dit product zit nog niet in een combination set.') }}</em>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
