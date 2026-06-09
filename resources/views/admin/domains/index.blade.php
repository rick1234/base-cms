@extends('layouts.admin')

@section('title', __('Domains'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container">
                <div class="buttons-container align-right">
                    <a class="btn btn-add" href="{{ route('admin.domains.create') }}">
                        <x-admin.material-icon name="add" />
                        {{ __('Start domain setup') }}
                    </a>
                    <a class="btn" href="{{ route('admin.templates.index') }}">
                        <x-admin.material-icon name="dashboard_customize" />
                        {{ __('Templates') }}
                    </a>
                </div>
            </div>

            <div class="main-section">
                @include('admin.content.partials.page-header', [
                    'title' => __('Domains'),
                    'section' => __('Domain overview'),
                    'icon' => 'domain',
                ])

                <livewire:admin.domains.domain-overview />
            </div>
        </div>
    </div>
@endsection
