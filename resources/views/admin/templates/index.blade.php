@extends('layouts.admin')

@section('title', __('Website Templates'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container">
                <div class="buttons-container align-right">
                    <a class="btn btn-add" href="{{ route('admin.templates.create') }}">
                        <x-admin.material-icon name="add" />
                        {{ __('Toevoegen') }}
                    </a>
                </div>
            </div>

            <div class="main-section">
                @include('admin.content.partials.page-header', [
                    'title' => __('Website Templates'),
                    'section' => __('Template overview'),
                ])

                <livewire:admin.templates.template-overview />
            </div>
        </div>
    </div>
@endsection
