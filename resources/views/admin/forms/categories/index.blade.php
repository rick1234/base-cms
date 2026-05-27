@extends('layouts.admin')

@section('title', __('Form Categories'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">

            <div class="main-section">
                @include('admin.forms.partials.page-header', [
                    'title' => __('Form Categories'),
                    'section' => __('Categorieen overzicht'),
                ])

                <livewire:admin.category-tree-manager module="forms" />
            </div>
        </div>
    </div>
@endsection
