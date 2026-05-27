@extends('layouts.admin')

@php
    $isExisting = (bool) $menu->id;
    $title = $isExisting ? __('Edit navigation: :name', ['name' => $menu->name]) : __('Create navigation menu');
    $oldItemsPayload = old('items_payload');
    $oldItems = is_string($oldItemsPayload) ? json_decode($oldItemsPayload, true) : null;
    $initialItems = is_array($oldItems) ? $oldItems : $itemsPayload;
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="navigation-menu-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                @if ($isExisting)
                    <form method="post" action="{{ route('admin.navigation.destroy', $menu) }}">
                        @csrf
                        @method('delete')
                        <button class="btn btn-remove" type="submit">
                            <span class="flaticon-close-button"></span>
                            {{ __('Verwijderen') }}
                        </button>
                    </form>
                @endif
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            <form
                id="navigation-menu-form"
                method="post"
                action="{{ $isExisting ? route('admin.navigation.update', $menu) : route('admin.navigation.store') }}"
                data-navigation-builder
                data-link-options-url="{{ route('admin.navigation.link-options') }}"
                data-link-types='@json($linkTypes)'
                data-initial-items='@json($initialItems)'
            >
                @csrf
                @if ($isExisting)
                    @method('put')
                @endif

                <input type="hidden" name="items_payload" value="{{ old('items_payload', json_encode($itemsPayload)) }}" data-navigation-payload>

                <div class="main-section">
                    @include('admin.content.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6">
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="name">{{ __('Name') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="name" name="name" type="text" value="{{ old('name', $menu->name) }}" required>
                                        @include('admin.content.partials.field-error', ['field' => 'name'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="handle">{{ __('Handle') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="handle" name="handle" type="text" value="{{ old('handle', $menu->handle) }}" required>
                                        @include('admin.content.partials.field-error', ['field' => 'handle'])
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="domain_id">{{ __('Domain') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="domain_id" name="domain_id">
                                            <option value="">{{ __('Global fallback') }}</option>
                                            @foreach ($domains as $domain)
                                                <option value="{{ $domain->id }}" @selected((int) old('domain_id', $menu->domain_id) === $domain->id)>{{ $domain->host }}</option>
                                            @endforeach
                                        </select>
                                        @include('admin.content.partials.field-error', ['field' => 'domain_id'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="locale">{{ __('Locale') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="locale" name="locale" type="text" value="{{ old('locale', $menu->locale) }}" maxlength="8">
                                        @include('admin.content.partials.field-error', ['field' => 'locale'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <label class="field-check">
                                        <input type="hidden" name="is_active" value="0">
                                        <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $menu->is_active ?? true))>
                                        {{ __('Active') }}
                                    </label>
                                    @include('admin.content.partials.field-error', ['field' => 'is_active'])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-section navigation-builder-section">
                    <div class="navigation-builder-header">
                        <div>
                            <h2 class="title">{{ __('Menu items') }}</h2>
                            @include('admin.content.partials.field-error', ['field' => 'items_payload'])
                        </div>
                        <button class="btn" type="button" data-navigation-open-selector>
                            <x-admin.material-icon name="add_link" />
                            {{ __('Add item') }}
                        </button>
                    </div>

                    <ol class="navigation-builder-list" data-navigation-list></ol>

                    <div class="navigation-empty-state" data-navigation-empty-state>
                        {{ __('No items yet.') }}
                    </div>
                </div>

                <div class="navigation-selector-modal" role="dialog" aria-modal="true" aria-labelledby="navigation-selector-title" hidden data-navigation-selector-modal>
                    <div class="navigation-selector-backdrop" data-navigation-selector-close></div>
                    <div class="navigation-selector-panel">
                        <div class="navigation-selector-header">
                            <h2 class="title" id="navigation-selector-title">{{ __('Select link') }}</h2>
                            <button class="config-button" type="button" data-navigation-selector-close aria-label="{{ __('Close') }}">
                                <x-admin.material-icon name="close" />
                            </button>
                        </div>

                        <div class="navigation-selector-types" data-navigation-selector-types></div>

                        <div class="navigation-selector-custom" hidden data-navigation-selector-custom>
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="navigation_custom_title">{{ __('Title') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="navigation_custom_title" type="text" data-navigation-custom-title>
                                </div>
                            </div>
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="navigation_custom_url">{{ __('URL') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="navigation_custom_url" type="text" data-navigation-custom-url>
                                </div>
                            </div>
                            <button class="btn" type="button" data-navigation-add-custom>
                                <x-admin.material-icon name="add" />
                                {{ __('Select') }}
                            </button>
                        </div>

                        <div class="navigation-selector-search" data-navigation-selector-search>
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="navigation_link_search">{{ __('Search') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="navigation_link_search" type="search" data-navigation-search-input autocomplete="off">
                                </div>
                            </div>

                            <div class="listing-container navigation-selector-results" data-navigation-results></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
