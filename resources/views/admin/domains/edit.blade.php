@extends('layouts.admin')

@php
    $pageTitle = $domain->exists ? __('Edit domain') : __('Create domain');
    $settings = [
        ...config('cms_domains.default_template_settings'),
        ...($domain->template?->default_settings ?? []),
        ...($domain->template_settings ?? []),
    ];
    $frontendLocales = old('active_frontend_locales', $domain->activeFrontendLocales());
    $backendLocales = old('active_backend_locales', $domain->activeBackendLocales());
    $aliasesText = old('aliases_text', $domain->exists ? $domain->aliases->pluck('host')->implode("\n") : '');
    $socialLinks = old('social_links', $domain->social_links ?? []);
    $publicIntegrations = old('public_integrations', $domain->public_integrations ?? []);

    while (count($socialLinks) < 5) {
        $socialLinks[] = ['platform' => '', 'label' => '', 'icon' => '', 'url' => ''];
    }
@endphp

@section('title', $pageTitle)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container">
                <div class="buttons-container align-right">
                    <button class="btn btn-save" form="domain-form" name="_next_step" type="submit" value="{{ $activeStep }}">
                        <x-admin.material-icon name="save" />
                        {{ __('Opslaan') }}
                    </button>
                    <a class="btn" href="{{ route('admin.domains.index') }}">
                        <x-admin.material-icon name="arrow_back" />
                        {{ __('Terug') }}
                    </a>
                    @if ($deleteAction)
                        <form method="post" action="{{ $deleteAction }}">
                            @csrf
                            @method('delete')
                            <button class="btn btn-delete" type="submit">
                                <x-admin.material-icon name="delete" />
                                {{ __('Verwijderen') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            @include('admin.domains.partials.item-tabs', [
                'active' => $activeStep,
                'domain' => $domain,
                'steps' => $steps,
            ])

            <div class="main-section">
                <div class="page-header">
                    <div class="page-header-title-container">
                        <div class="page-header-title-image-container">
                            <x-admin.material-icon class="is-large" name="domain" />
                        </div>
                        <strong>{{ $pageTitle }}</strong>
                    </div>
                </div>

                <form id="domain-form" class="edit-form" method="post" action="{{ $action }}" enctype="multipart/form-data">
                    @csrf
                    @if ($method !== 'post')
                        @method($method)
                    @endif

                    <input type="hidden" name="_domain_step" value="{{ $activeStep }}">

                    @include("admin.domains.steps.{$activeStep}")
                </form>
            </div>
        </div>
    </div>
@endsection
