@extends('layouts.admin')

@section('title', __('Catalog Categories'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">

            <div class="main-section">
                @include('admin.catalog.partials.page-header', [
                    'title' => __('Catalog Categories'),
                    'section' => __('Categorieen overzicht'),
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                <livewire:admin.category-tree-manager module="catalog" />
            </div>
        </div>
    </div>
@endsection
