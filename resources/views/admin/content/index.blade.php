@extends('layouts.admin')

@section('title', __('Pages'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container">
                <div class="buttons-container align-right">
                    <a class="btn btn-add" href="{{ route($routeNames['create']) }}">
                        <x-admin.material-icon name="add" />
                        {{ __('Toevoegen') }}
                    </a>
                    <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.content.categories.index' : 'admin.content.categories.index') }}">
                        <x-admin.material-icon name="folder" />
                        {{ __('Categorieen') }}
                    </a>
                </div>
            </div>

            <div class="main-section">
                @include('admin.content.partials.page-header', [
                    'title' => __('Pages'),
                    'section' => __('Pages overview'),
                ])

                <livewire:admin.listing-overview module="content" />
            </div>
        </div>
    </div>
@endsection
