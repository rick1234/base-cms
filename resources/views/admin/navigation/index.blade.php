@extends('layouts.admin')

@php
    $showTechnicalName = (bool) auth()->user()?->is_admin;
@endphp

@section('title', __('Navigation'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <a class="btn btn-add" href="{{ route('admin.navigation.create') }}">
                    <x-admin.material-icon name="add" />
                    {{ __('Toevoegen') }}
                </a>
            </div>

            <div class="main-section">
                @include('admin.content.partials.page-header', [
                    'title' => __('Navigation'),
                    'section' => __('Navigation overview'),
                ])

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                <div class="overview-container">
                    <div class="overview-row header">
                        <div class="overview-item id">{{ __('ID') }}</div>
                        <div class="overview-item name">{{ __('Name') }}</div>
                        @if ($showTechnicalName)
                            <div class="overview-item title">{{ __('Handle') }}</div>
                        @endif
                        <div class="overview-item language">{{ __('Taal') }}</div>
                        <div class="overview-item title">{{ __('Domain') }}</div>
                        <div class="overview-item status">{{ __('Status') }}</div>
                        <div class="overview-item status">{{ __('Items') }}</div>
                        <div class="overview-item options">{{ __('Options') }}</div>
                    </div>

                    <form method="get" action="{{ route('admin.navigation.index') }}">
                        <div class="overview-row filters">
                            <div class="overview-item id">
                                <input name="id" type="text" value="{{ request('id') }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item name">
                                <input name="name" type="text" value="{{ request('name') }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            @if ($showTechnicalName)
                                <div class="overview-item title">
                                    <input name="handle" type="text" value="{{ request('handle') }}">
                                    <x-admin.material-icon name="search" class="search-icon" />
                                </div>
                            @endif
                            <div class="overview-item language">
                                <select name="locale">
                                    <option value="">{{ __('Selecteer') }}</option>
                                    <option value="fallback" @selected(request('locale') === 'fallback')>{{ __('Fallback') }}</option>
                                    @foreach ($languages as $language)
                                        @php $languageCode = is_string($language) ? $language : $language->code; @endphp
                                        <option value="{{ $languageCode }}" @selected(request('locale') === $languageCode)>{{ strtoupper($languageCode) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="overview-item title">
                                <select name="domain_id">
                                    <option value="">{{ __('Selecteer') }}</option>
                                    <option value="global" @selected(request('domain_id') === 'global')>{{ __('Global') }}</option>
                                    @foreach ($domains as $domain)
                                        <option value="{{ $domain->id }}" @selected((string) request('domain_id') === (string) $domain->id)>{{ $domain->host }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="overview-item status">
                                <select name="status">
                                    <option value="">{{ __('Kies een status') }}</option>
                                    <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
                                </select>
                            </div>
                            <div class="overview-item status">
                                <input name="items_count" type="text" value="{{ request('items_count') }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item options">
                                <button type="submit" title="{{ __('Search') }}">
                                    <x-admin.material-icon name="search" />
                                </button>
                                <a href="{{ route('admin.navigation.index') }}" title="{{ __('Reset') }}">
                                    <x-admin.material-icon name="close" />
                                </a>
                            </div>
                        </div>
                    </form>

                    @forelse ($menus as $menu)
                        <div class="overview-row">
                            <div class="overview-item id">{{ $menu->id }}</div>
                            <div class="overview-item name">{{ $menu->name }}</div>
                            @if ($showTechnicalName)
                                <div class="overview-item title">{{ $menu->handle }}</div>
                            @endif
                            <div class="overview-item language">
                                @if ($menu->locale)
                                    <x-admin.language-flag :locale="$menu->locale" :label="strtoupper($menu->locale)" />
                                    <span>{{ strtoupper($menu->locale) }}</span>
                                @else
                                    <span>{{ __('Fallback') }}</span>
                                @endif
                            </div>
                            <div class="overview-item title">{{ $menu->domain?->host ?? __('Global') }}</div>
                            <div class="overview-item status">
                                <x-admin.quick-status model="navigation-menu" :record="$menu" :value="$menu->is_active" :active="$menu->is_active" />
                            </div>
                            <div class="overview-item status">{{ $menu->items_count }}</div>
                            <div class="overview-item options">
                                <a href="{{ route('admin.navigation.edit', $menu) }}" aria-label="{{ __('Edit') }}" title="{{ __('Edit') }}">
                                    <x-admin.material-icon name="edit" />
                                </a>
                                <form method="post" action="{{ route('admin.navigation.destroy', $menu) }}">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" aria-label="{{ __('Delete') }}" title="{{ __('Delete') }}">
                                        <x-admin.material-icon name="delete" class="admin-delete-icon" />
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="overview-row">
                            <div class="overview-item title">{{ __('No navigation menus found.') }}</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
