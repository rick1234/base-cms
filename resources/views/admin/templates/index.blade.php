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
                        <span class="flaticon-add-plus-button"></span>
                        {{ __('Toevoegen') }}
                    </a>
                </div>
            </div>

            <div class="main-section">
                <div class="page-header">
                    <div class="page-header-title-container">
                        <div class="page-header-title-image-container">
                            <x-admin.material-icon class="is-large" name="dashboard_customize" />
                        </div>
                        <strong>{{ __('Website Templates') }}</strong>
                    </div>
                </div>

                <div class="breadcrumbs">
                    <a href="{{ route('admin.dashboard') }}">{{ __('Home') }}</a> &rsaquo;
                    {{ __('Website Templates') }}
                </div>

                <div class="overview-container">
                    <div class="overview-row header">
                        <div class="overview-item">{{ __('Name') }}</div>
                        <div class="overview-item">{{ __('Handle') }}</div>
                        <div class="overview-item">{{ __('Domains') }}</div>
                        <div class="overview-item">{{ __('Status') }}</div>
                        <div class="overview-item options">{{ __('Options') }}</div>
                    </div>

                    @forelse ($templates as $template)
                        <div class="overview-row">
                            <div class="overview-item">
                                <a href="{{ route('admin.templates.edit', $template) }}">{{ $template->name }}</a>
                            </div>
                            <div class="overview-item">{{ $template->handle }}</div>
                            <div class="overview-item">{{ $template->domains_count }}</div>
                            <div class="overview-item">{{ $template->is_active ? __('Active') : __('Inactive') }}</div>
                            <div class="overview-item options">
                                <a href="{{ route('admin.templates.edit', $template) }}" title="{{ __('Edit') }}">
                                    <span class="flaticon-create-new-pencil-button"></span>
                                </a>
                                <form method="post" action="{{ route('admin.templates.destroy', $template) }}">
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
                            <div class="overview-item">{{ __('No templates have been configured yet.') }}</div>
                        </div>
                    @endforelse
                </div>

                {{ $templates->links('admin.partials.pagination') }}
            </div>
        </div>
    </div>
@endsection
