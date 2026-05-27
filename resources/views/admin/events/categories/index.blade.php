@extends('layouts.admin')

@section('title', __('Event Categories'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">

            <div class="main-section">
                @include('admin.events.partials.page-header', [
                    'title' => __('Event Categories'),
                    'section' => __('Categorieen overzicht'),
                    'eventsUrl' => route(request()->routeIs('cms.*') ? 'cms.events.index' : 'admin.events.index'),
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                <livewire:admin.category-tree-manager module="events" />
            </div>
        </div>
    </div>
@endsection
