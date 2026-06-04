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
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
                </a>
            </div>

            <div class="main-section">
                @include('admin.locations.partials.page-header', [
                    'title' => __('Locations'),
                    'section' => $pageName,
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                <div class="attachment-message">
                    <x-admin.material-icon name="info" />
                    <em>{{ __('Selecteer eerst een vestiging om deze instellingen te beheren.') }}</em>
                </div>
            </div>
        </div>
    </div>
@endsection
