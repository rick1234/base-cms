@extends('layouts.admin')

@section('title', __('Location Categories'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                @include('admin.partials.category-related-items-button')
            </div>

            <div class="main-section">
                @include('admin.locations.partials.page-header', [
                    'title' => __('Location Categories'),
                    'section' => __('Location category overview'),
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                <livewire:admin.category-tree-manager module="locations" />
            </div>
        </div>
    </div>
@endsection
