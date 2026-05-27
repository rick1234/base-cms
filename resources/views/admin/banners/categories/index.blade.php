@extends('layouts.admin')

@section('title', __('Banner Categories'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">

            <div class="main-section">
                @include('admin.banners.partials.page-header', [
                    'title' => __('Banner Categories'),
                    'section' => __('Categorieen overzicht'),
                ])

                <livewire:admin.category-tree-manager module="banners" />
            </div>
        </div>
    </div>
@endsection
