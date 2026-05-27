@extends('layouts.admin')

@section('title', __('User Categories'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">

            <div class="main-section">
                @include('admin.users.partials.page-header', [
                    'title' => __('User Categories'),
                    'section' => __('Categorieen overzicht'),
                    'usersUrl' => route(request()->routeIs('cms.*') ? 'cms.users.index' : 'admin.users.index'),
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                <livewire:admin.category-tree-manager module="users" />
            </div>
        </div>
    </div>
@endsection
