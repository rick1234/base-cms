@extends('layouts.admin')

@section('title', __('Location images'))

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
                @include('admin.locations.partials.page-header', [
                    'title' => $location->name,
                    'section' => $pageName,
                ])

                @include('admin.locations.partials.tabs', [
                    'location' => $location,
                    'routeNames' => $routeNames,
                    'activeTab' => 'images',
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                <h2 class="title">{{ __('Selecteer bestanden') }}</h2>
                <form enctype="multipart/form-data" method="post" action="{{ route($routeNames['image.upload'], ['id' => $location->id]) }}">
                    @csrf
                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="location_image">{{ __('Afbeelding') }}</label>
                        </div>
                        <div class="form-item-input">
                            <input id="location_image" name="image" type="file" accept="image/*" required>
                            <input name="caption" type="text" placeholder="{{ __('Titel') }}">
                            <button class="btn btn-add" type="submit">
                                <x-admin.material-icon name="add" />
                                {{ __('Uploaden') }}
                            </button>
                        </div>
                    </div>
                </form>

                <h3 class="sub-title">{{ __('Reeds gekoppelde afbeeldingen') }}</h3>
                @include('admin.content.partials.media-items', [
                    'images' => $location->images,
                    'deleteRoute' => $routeNames['image.delete'],
                    'updateNameRoute' => $routeNames['image.update-name'],
                ])
            </div>
        </div>
    </div>
@endsection
