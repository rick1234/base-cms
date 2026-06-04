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
                @include('admin.catalog.partials.page-header', [
                    'title' => __('Catalog Products'),
                    'section' => $pageName,
                ])

                <h2 class="title">{{ $pageName }}</h2>
                <div class="attachment-message">
                    <x-admin.material-icon name="info" />
                    <em>{{ __('Selecteer eerst een product om deze catalogusfunctie te gebruiken.') }}</em>
                </div>
            </div>
        </div>
    </div>
@endsection
