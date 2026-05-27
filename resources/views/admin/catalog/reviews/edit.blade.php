@extends('layouts.admin')

@php
    $isExisting = (bool) $review->id;
    $title = $isExisting ? __('Bewerk review') : __('Toevoegen');
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="catalog-review-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                @if ($isExisting)
                    <form method="post" action="{{ $deleteAction }}">
                        @csrf
                        @method('delete')
                        <button class="btn btn-remove" type="submit">
                            <span class="flaticon-close-button"></span>
                            {{ __('Verwijderen') }}
                        </button>
                    </form>
                @endif
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            <form id="catalog-review-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $review->id])) }}">
                @csrf
                <input type="hidden" name="id" value="{{ $review->id }}">

                <div class="main-section">
                    @include('admin.catalog.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <div class="content-section">
                        <h2 class="title">{{ __('Algemeen') }}</h2>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="catalog_product_id">{{ __('Product') }}</label>
                            </div>
                            <div class="form-item-input">
                                <select id="catalog_product_id" name="catalog_product_id" required>
                                    <option value="">{{ __('Selecteer') }}</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" @selected((int) old('catalog_product_id', $review->catalog_product_id) === $product->id)>{{ $product->name }}</option>
                                    @endforeach
                                </select>
                                @include('admin.content.partials.field-error', ['field' => 'catalog_product_id'])
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="author_name">{{ __('Naam') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="author_name" name="author_name" type="text" value="{{ old('author_name', $review->author_name) }}">
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="author_email">{{ __('E-mail') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="author_email" name="author_email" type="email" value="{{ old('author_email', $review->author_email) }}">
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="rating">{{ __('Rating') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="rating" name="rating" type="number" min="1" max="5" value="{{ old('rating', $review->rating) }}">
                                @include('admin.content.partials.field-error', ['field' => 'rating'])
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="status">{{ __('Status') }}</label>
                            </div>
                            <div class="form-item-input">
                                <select id="status" name="status">
                                    <option value="pending" @selected(old('status', $review->status) === 'pending')>{{ __('Pending') }}</option>
                                    <option value="published" @selected(old('status', $review->status) === 'published')>{{ __('Published') }}</option>
                                    <option value="rejected" @selected(old('status', $review->status) === 'rejected')>{{ __('Rejected') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="title">{{ __('Titel') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="title" name="title" type="text" value="{{ old('title', $review->title) }}">
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="content">{{ __('Review') }}</label>
                            </div>
                            <div class="form-item-input">
                                <textarea id="content" name="content">{{ old('content', $review->content) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
