@extends('layouts.admin')

@php
    $title = $contentItem ? __('Slider: :title', ['title' => $contentItem->title]) : __('Content slider');
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                @if ($contentItem)
                    <button class="btn btn-save" form="content-slider-form" type="submit">
                        <span class="flaticon-save-button"></span>
                        {{ __('Opslaan') }}
                    </button>
                @endif
                <a href="{{ $contentItem ? route($routeNames['edit'], ['id' => $contentItem->id]) : $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            @include('admin.content.partials.item-tabs', [
                'active' => 'slider',
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
                        <em>{{ __('Sla het content item eerst op voordat u een slider koppelt.') }}</em>
                    </div>
                @else

                    <form id="content-slider-form" method="post" action="{{ route($routeNames['slider.save'], ['id' => $contentItem->id]) }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $contentItem->id }}">

                        <div class="slider-choice-container">
                            <div class="choice">
                                <h2 class="title">{{ __('Slider') }}</h2>
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="slider_category_id">{{ __('Kies een slider categorie') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="slider_category_id" name="slider_category_id">
                                            <option value="">{{ __('Geen slider') }}</option>
                                            @foreach ($sliderCategories as $sliderCategory)
                                                <option value="{{ $sliderCategory->id }}" @selected((int) old('slider_category_id', $contentItem->slider_category_id) === $sliderCategory->id)>{{ $sliderCategory->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="choice">
                                <h2 class="title">{{ __('Categorie sliders') }}</h2>
                                @if ($contentItem->categories->isNotEmpty())
                                    <ul class="content-related-list">
                                        @foreach ($contentItem->categories as $category)
                                            <li>
                                                <a href="{{ route(request()->routeIs('cms.*') ? 'cms.content.categories.slider' : 'admin.content.categories.slider', ['id' => $category->id]) }}">
                                                    {{ $category->name }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p>{{ __('Dit content item is nog niet aan categorieen gekoppeld.') }}</p>
                                @endif
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
