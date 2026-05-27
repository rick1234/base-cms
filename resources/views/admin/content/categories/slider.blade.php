@extends('layouts.admin')

@php
    $title = $category ? __('Slider: :name', ['name' => $category->name]) : __('Category slider');
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                @if ($category)
                    <button class="btn btn-save" form="content-category-slider-form" type="submit">
                        <span class="flaticon-save-button"></span>
                        {{ __('Opslaan') }}
                    </button>
                @endif
                <a href="{{ $category ? route($routeNames['edit'], ['id' => $category->id]) : $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            @include('admin.content.partials.category-tabs', [
                'active' => 'slider',
                'category' => $category,
                'routeNames' => $routeNames,
            ])

            <div class="main-section">
                @include('admin.content.partials.page-header', [
                    'title' => $title,
                    'section' => $pageName,
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                @if (! $category)
                    <div class="attachment-message">
                        <span class="flaticon-rounded-info-button"></span>
                        <em>{{ __('Sla de categorie eerst op voordat u een slider koppelt.') }}</em>
                    </div>
                @else

                    <form id="content-category-slider-form" method="post" action="{{ route($routeNames['slider.save'], ['id' => $category->id]) }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $category->id }}">

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
                                                <option value="{{ $sliderCategory->id }}" @selected((int) old('slider_category_id', $category->slider_category_id) === $sliderCategory->id)>{{ $sliderCategory->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="choice">
                                <h2 class="title">{{ __('Categorie') }}</h2>
                                <dl class="cms-module-details">
                                    <dt>{{ __('Naam') }}</dt>
                                    <dd>{{ $category->name }}</dd>
                                    <dt>{{ __('Slug') }}</dt>
                                    <dd>{{ $category->slug }}</dd>
                                </dl>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
