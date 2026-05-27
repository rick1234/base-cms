@extends('layouts.admin')

@section('title', __('Website languages'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="language-settings-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <a href="{{ route($routeNames['create']) }}" class="btn btn-add">
                    <span class="flaticon-add-plus-button"></span>
                    {{ __('Toevoegen') }}
                </a>
                <a href="{{ route(request()->routeIs('cms.*') ? 'cms.translations.index' : 'admin.translations.index') }}" class="btn">
                    <span class="admin-symbol admin-symbol-translation" aria-hidden="true"></span>
                    {{ __('Translations') }}
                </a>
                <a href="{{ route($routeNames['countries']) }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Countries') }}
                </a>
            </div>

            <div class="main-section">
                @include('admin.localization.partials.page-header', [
                    'title' => __('Website languages'),
                    'section' => __('Website languages'),
                ])

                <span class="content-admin-screen-label">{{ __('Website languages') }}</span>

                <div class="language-summary-list">
                    @forelse ($enabledLanguages as $language)
                        <span class="language-summary-item {{ $language->is_default ? 'is-default' : '' }}">
                            {{ $language->label() }}
                        </span>
                    @empty
                        <span class="language-summary-item">{{ __('No website languages enabled yet.') }}</span>
                    @endforelse
                </div>

                <form id="language-settings-form" method="post" action="{{ route($routeNames['save-settings']) }}">
                    @csrf
                    @include('admin.content.partials.field-error', ['field' => 'default_language'])
                    @include('admin.content.partials.field-error', ['field' => 'enabled_languages'])

                    <div class="overview-container languages-overview-container">
                        <div class="overview-row header">
                            <div class="overview-item default">{{ __('Default') }}</div>
                            <div class="overview-item enabled">{{ __('Website') }}</div>
                            <div class="overview-item code">{{ __('Code') }}</div>
                            <div class="overview-item name">{{ __('Naam') }}</div>
                            <div class="overview-item direction">{{ __('Direction') }}</div>
                            <div class="overview-item status">{{ __('Actief') }}</div>
                            <div class="overview-item options">{{ __('Opties') }}</div>
                        </div>

                        @foreach ($languages as $language)
                            <div class="overview-row">
                                <div class="overview-item default">
                                    <input id="default_language_{{ $language->id }}" name="default_language" type="radio" value="{{ $language->id }}" @checked((int) old('default_language', optional($defaultLanguage)->id) === $language->id)>
                                </div>
                                <div class="overview-item enabled">
                                    <input id="enabled_language_{{ $language->id }}" name="enabled_languages[]" type="checkbox" value="{{ $language->id }}" @checked(in_array($language->id, old('enabled_languages', $enabledLanguages->pluck('id')->all()), false))>
                                </div>
                                <div class="overview-item code">
                                    <label for="enabled_language_{{ $language->id }}">{{ $language->code }}</label>
                                </div>
                                <div class="overview-item name">
                                    <a href="{{ route($routeNames['edit'], ['id' => $language->id]) }}">{{ $language->name }}</a>
                                    @if ($language->native_name && $language->native_name !== $language->name)
                                        <small>{{ $language->native_name }}</small>
                                    @endif
                                </div>
                                <div class="overview-item direction">{{ strtoupper($language->direction) }}</div>
                                <div class="overview-item status">
                                    <span class="{{ $language->status === 'active' ? 'active-item' : 'inactive-item' }}"></span>
                                </div>
                                <div class="overview-item options">
                                    <a href="{{ route($routeNames['edit'], ['id' => $language->id]) }}" title="{{ __('Bewerken') }}">
                                        <span class="flaticon-create-new-pencil-button"></span>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
