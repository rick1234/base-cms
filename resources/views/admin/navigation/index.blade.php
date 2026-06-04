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
                                <span class="{{ $menu->is_active ? 'active-item' : 'inactive-item' }}" title="{{ $menu->is_active ? __('Active') : __('Inactive') }}"></span>
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
