@extends('layouts.admin')

@section('title', __('FAQ Categories'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">

            <div class="main-section">
                @include('admin.faq.partials.page-header', [
                    'title' => __('FAQ Categories'),
                    'section' => __('FAQ category overview'),
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                <livewire:admin.category-tree-manager module="faq" />
            </div>
        </div>
    </div>
@endsection
