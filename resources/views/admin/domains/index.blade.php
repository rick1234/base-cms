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
                        <span class="flaticon-add-plus-button"></span>
                        {{ __('Start domain setup') }}
                    </a>
                    <a class="btn" href="{{ route('admin.templates.index') }}">
                        <x-admin.material-icon name="dashboard_customize" />
                        {{ __('Templates') }}
                    </a>
                </div>
            </div>

            <div class="main-section">
                <div class="page-header">
                    <div class="page-header-title-container">
                        <div class="page-header-title-image-container">
                            <x-admin.material-icon class="is-large" name="domain" />
                        </div>
                        <strong>{{ __('Domains') }}</strong>
                    </div>
                </div>

                <div class="breadcrumbs">
                    <a href="{{ route('admin.dashboard') }}">{{ __('Home') }}</a> &rsaquo;
                    {{ __('Domains') }}
                </div>

                <div class="overview-container">
                    <div class="overview-row header">
                        <div class="overview-item">{{ __('Host') }}</div>
                        <div class="overview-item">{{ __('Website title') }}</div>
                        <div class="overview-item">{{ __('Template') }}</div>
                        <div class="overview-item">{{ __('Locale') }}</div>
                        <div class="overview-item">{{ __('Status') }}</div>
                        <div class="overview-item options">{{ __('Options') }}</div>
                    </div>

                    @forelse ($domains as $domain)
                        <div class="overview-row">
                            <div class="overview-item">
                                <a href="{{ route('admin.domains.edit', $domain) }}">{{ $domain->host }}</a>
                            </div>
                            <div class="overview-item">{{ $domain->name }}</div>
                            <div class="overview-item">{{ $domain->template?->name ?? __('None') }}</div>
                            <div class="overview-item">{{ strtoupper($domain->default_locale ?: config('app.locale')) }}</div>
                            <div class="overview-item">{{ $domain->is_active ? __('Active') : __('Inactive') }}</div>
                            <div class="overview-item options">
                                <a href="{{ route('admin.domains.edit', $domain) }}" title="{{ __('Edit') }}">
                                    <span class="flaticon-create-new-pencil-button"></span>
                                </a>
                                <form method="post" action="{{ route('admin.domains.destroy', $domain) }}">
                                    @csrf
                                    @method('delete')
                                    <button class="button-link" type="submit" title="{{ __('Delete') }}">
                                        <span class="flaticon-delete-button"></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="overview-row">
                            <div class="overview-item">{{ __('No domains have been configured yet.') }}</div>
                        </div>
                    @endforelse
                </div>

                {{ $domains->links('admin.partials.pagination') }}
            </div>
        </div>
    </div>
@endsection
