@extends('layouts.admin')

@section('title', $pageName)

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
                @include('admin.locations.partials.page-header', [
                    'title' => __('Locations'),
                    'section' => $pageName,
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                <div class="attachment-message">
                    <span class="flaticon-rounded-info-button"></span>
                    <em>{{ __('Selecteer eerst een vestiging om deze instellingen te beheren.') }}</em>
                </div>
            </div>
        </div>
    </div>
@endsection
