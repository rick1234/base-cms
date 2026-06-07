@extends('layouts.admin')

@section('title', __('Form messages'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
                </a>
            </div>

            <div class="main-section">
                @include('admin.forms.partials.page-header', [
                    'title' => __('Form messages'),
                    'section' => $form ? $form->name : __('Berichten'),
                ])

                @if ($form)
                    @include('admin.forms.partials.tabs', [
                        'form' => $form,
                        'routeNames' => $routeNames,
                        'activeTab' => 'submissions',
                    ])
                @endif

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                @if (! $form)
                    <div class="attachment-message">
                        <x-admin.material-icon name="info" />
                        <em>{{ __('Selecteer eerst een formulier.') }}</em>
                    </div>
                @else
                    <livewire:admin.forms.form-submission-inbox :form="$form" />
                @endif
            </div>
        </div>
    </div>
@endsection
