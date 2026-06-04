@extends('layouts.admin')

@php
    $title = $contentItem ? __('Slider: :title', ['title' => $contentItem->title]) : __('Page slider');
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
                        <x-admin.material-icon name="save" />
                        {{ __('Opslaan') }}
                    </button>
                @endif
                <a href="{{ $contentItem ? route($routeNames['edit'], ['id' => $contentItem->id]) : $backUrl }}" class="btn btn-cancel">
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
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
                        <x-admin.material-icon name="info" />
                        <em>{{ __('Save the page before linking a slider.') }}</em>
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
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
