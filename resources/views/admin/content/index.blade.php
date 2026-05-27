@extends('layouts.admin')

@section('title', __('Content'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container">
                <div class="buttons-container align-right">
                    <a class="btn btn-add" href="{{ route($routeNames['create']) }}">
                        <span class="flaticon-add-plus-button"></span>
                        {{ __('Toevoegen') }}
                    </a>
                    <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.content.categories.index' : 'admin.content.categories.index') }}">
                        <span class="flaticon-folder-symbol"></span>
                        {{ __('Categorieen') }}
                    </a>
                </div>
            </div>

            <div class="main-section">
                @include('admin.content.partials.page-header', [
                    'title' => __('Content'),
                    'section' => __('Berichten overzicht'),
                ])

                <livewire:admin.listing-overview module="content" />
            </div>
        </div>
    </div>
@endsection
