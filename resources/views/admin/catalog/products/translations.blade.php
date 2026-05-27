@extends('layouts.admin')

@php
    $locales = collect(['nl', 'en', 'de', 'fr']);
    $rows = $product->translations->values();
    $missingLocales = $locales->diff($rows->pluck('locale'));
@endphp

@section('title', __('Product translations'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="catalog-translations-form" type="submit">
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
                    'activeTab' => 'translations',
                ])

                <form id="catalog-translations-form" method="post" action="{{ route($routeNames['translations.save'], ['id' => $product->id]) }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $product->id }}">

                    <h2 class="title">{{ __('Vertalingen') }}</h2>

                    @foreach ($rows as $index => $translation)
                        <div class="content-section">
                            <input type="hidden" name="translations[{{ $index }}][id]" value="{{ $translation->id }}">
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation_locale_{{ $index }}">{{ __('Taal') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="translation_locale_{{ $index }}" name="translations[{{ $index }}][locale]" type="text" value="{{ old("translations.{$index}.locale", $translation->locale) }}">
                                </div>
                            </div>
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation_title_{{ $index }}">{{ __('Titel') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="translation_title_{{ $index }}" name="translations[{{ $index }}][title]" type="text" value="{{ old("translations.{$index}.title", $translation->title) }}">
                                </div>
                            </div>
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation_subtitle_{{ $index }}">{{ __('Subtitel') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="translation_subtitle_{{ $index }}" name="translations[{{ $index }}][subtitle]" type="text" value="{{ old("translations.{$index}.subtitle", $translation->subtitle) }}">
                                </div>
                            </div>
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation_content_{{ $index }}">{{ __('Tekst') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <textarea id="translation_content_{{ $index }}" name="translations[{{ $index }}][content]">{{ old("translations.{$index}.content", $translation->content) }}</textarea>
                                </div>
                            </div>
                            <div class="form-item">
                                <div class="form-item-label">{{ __('Verwijderen') }}</div>
                                <div class="form-item-input">
                                    <label>
                                        <input name="translations[{{ $index }}][delete]" type="checkbox" value="1">
                                        <span class="checkbox"></span>
                                        {{ __('Deze vertaling verwijderen') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @foreach ($missingLocales as $newIndex => $locale)
                        @php $index = $rows->count() + $newIndex; @endphp
                        <div class="content-section">
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation_locale_{{ $index }}">{{ __('Taal') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="translation_locale_{{ $index }}" name="translations[{{ $index }}][locale]" type="text" value="{{ old("translations.{$index}.locale", $locale) }}">
                                </div>
                            </div>
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation_title_{{ $index }}">{{ __('Titel') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="translation_title_{{ $index }}" name="translations[{{ $index }}][title]" type="text" value="{{ old("translations.{$index}.title") }}">
                                </div>
                            </div>
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation_subtitle_{{ $index }}">{{ __('Subtitel') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="translation_subtitle_{{ $index }}" name="translations[{{ $index }}][subtitle]" type="text" value="{{ old("translations.{$index}.subtitle") }}">
                                </div>
                            </div>
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation_content_{{ $index }}">{{ __('Tekst') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <textarea id="translation_content_{{ $index }}" name="translations[{{ $index }}][content]">{{ old("translations.{$index}.content") }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </form>
            </div>
        </div>
    </div>
@endsection
