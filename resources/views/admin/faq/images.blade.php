@extends('layouts.admin')

@section('title', __('FAQ images'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            <div class="main-section">
                @include('admin.faq.partials.page-header', [
                    'title' => $faqItem->question,
                    'section' => $pageName,
                ])

                @include('admin.faq.partials.tabs', [
                    'faqItem' => $faqItem,
                    'routeNames' => $routeNames,
                    'activeTab' => 'images',
                ])

                <h2 class="title">{{ __('Selecteer bestanden') }}</h2>
                <form enctype="multipart/form-data" method="post" action="{{ route($routeNames['image.upload'], ['id' => $faqItem->id]) }}">
                    @csrf
                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="faq_image">{{ __('Afbeelding') }}</label>
                        </div>
                        <div class="form-item-input">
                            <input id="faq_image" name="image" type="file" accept="image/*" required>
                            <input name="caption" type="text" placeholder="{{ __('Titel') }}">
                            <button class="btn" type="submit">
                                <span class="flaticon-add-plus-button"></span>
                                {{ __('Uploaden') }}
                            </button>
                        </div>
                    </div>
                </form>

                <h3 class="sub-title">{{ __('Reeds gekoppelde afbeeldingen') }}</h3>
                @include('admin.content.partials.media-items', [
                    'images' => $faqItem->images,
                    'deleteRoute' => $routeNames['image.delete'],
                    'updateNameRoute' => $routeNames['image.update-name'],
                ])
            </div>
        </div>
    </div>
@endsection
