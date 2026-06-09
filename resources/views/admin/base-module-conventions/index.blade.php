@extends('layouts.admin')

@section('title', __('Base module conventions'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container">
                <div class="buttons-container align-right">
                    <a class="btn btn-add" href="{{ route($routeNames['create']) }}">
                        <x-admin.material-icon name="add" />
                        {{ __('Toevoegen') }}
                    </a>
                    <a class="btn" href="{{ route($routeNames['categories']) }}">
                        <x-admin.material-icon name="folder" />
                        {{ __('Categorieen') }}
                    </a>
                </div>
            </div>

            <div class="main-section">
                @include('admin.content.partials.page-header', [
                    'title' => __('Base module conventions'),
                    'section' => __('Base module conventions overview'),
                ])

                <div class="listing-overview">
                    <div class="overview-container content-overview-container listing-overview-container">
                        <div class="overview-row header">
                            <div class="overview-item id">
                                Id
                                <button type="button" title="{{ __('Nieuwste eerst') }}">
                                    <x-admin.material-icon name="keyboard_arrow_up" />
                                </button>
                                <button type="button" title="{{ __('Oudste eerst') }}">
                                    <x-admin.material-icon name="keyboard_arrow_down" />
                                </button>
                            </div>
                            <div class="overview-item language">{{ __('Taal') }}</div>
                            <div class="overview-item category">{{ __('Categorie') }}</div>
                            <div class="overview-item title">
                                {{ __('Titel') }}
                                <button type="button" title="{{ __('Sorteer aflopend') }}">
                                    <x-admin.material-icon name="keyboard_arrow_up" />
                                </button>
                                <button type="button" title="{{ __('Sorteer oplopend') }}">
                                    <x-admin.material-icon name="keyboard_arrow_down" />
                                </button>
                                <span class="listing-sort-state" aria-live="polite"></span>
                            </div>
                            <div class="overview-item status">{{ __('Status') }}</div>
                            <div class="overview-item options">{{ __('Opties') }}</div>
                        </div>

                        <form class="overview-row filters" method="get" action="{{ route($routeNames['index']) }}">
                            <div class="overview-item id">
                                <input name="id" type="text">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item language">
                                <select name="locale" aria-label="{{ __('Taal') }}">
                                    <option value="">{{ __('Alle talen') }}</option>
                                    <option value="nl">{{ __('Dutch') }}</option>
                                    <option value="en">{{ __('English') }}</option>
                                </select>
                            </div>
                            <div class="overview-item category">
                                <div class="listing-category-picker">
                                    <button class="listing-category-picker-button" type="button">
                                        <x-admin.material-icon name="folder" />
                                        <span>{{ __('Selecteer') }}</span>
                                    </button>
                                </div>
                            </div>
                            <div class="overview-item title">
                                <input name="title" type="text">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item status">
                                <select name="status">
                                    <option value="">{{ __('Selecteer') }}</option>
                                    <option value="published">{{ __('Online') }}</option>
                                    <option value="draft">{{ __('Offline') }}</option>
                                    <option value="archived">{{ __('Archived') }}</option>
                                </select>
                            </div>
                            <div class="overview-item options">
                                <button type="submit" title="{{ __('Zoeken') }}">
                                    <x-admin.material-icon name="search" />
                                </button>
                                <button type="button" title="{{ __('Reset') }}">
                                    <x-admin.material-icon name="close" />
                                </button>
                            </div>
                        </form>

                        @foreach ($fakeRows as $row)
                            <div class="overview-row">
                                <div class="overview-item id">{{ $row['id'] }}</div>
                                <div class="overview-item language">
                                    <x-admin.language-flag :locale="$row['locale']" />
                                </div>
                                <div class="overview-item category">{{ $row['category'] }}</div>
                                <div class="overview-item title">{{ $row['title'] }}</div>
                                <div class="overview-item status">
                                    <details class="quick-status">
                                        <summary class="quick-status-trigger" aria-label="{{ __('Change status for :item', ['item' => $row['status'] === 'published' ? __('Online') : __('Offline')]) }}" title="{{ __('Change status') }}">
                                            <span class="{{ $row['status'] === 'published' ? 'active-item' : 'inactive-item' }}" aria-hidden="true"></span>
                                        </summary>
                                        <button class="quick-status-backdrop" type="button" aria-label="{{ __('Close') }}" data-quick-status-close></button>
                                        <div class="quick-status-modal" role="dialog" aria-modal="true">
                                            <div class="quick-status-modal-panel">
                                                <div class="quick-status-modal-header">
                                                    <div class="quick-status-modal-title">
                                                        <h2>{{ __('Change status') }}</h2>
                                                        <span>{{ __('Available statuses') }}</span>
                                                    </div>
                                                    <button class="quick-status-modal-close" type="button" aria-label="{{ __('Close') }}" data-quick-status-close>
                                                        <x-admin.material-icon name="close" />
                                                    </button>
                                                </div>
                                                <div class="quick-status-options">
                                                    <button class="quick-status-option {{ $row['status'] === 'published' ? 'is-selected' : '' }}" type="button">
                                                        <span class="quick-status-dot is-published" aria-hidden="true"></span>
                                                        <span>{{ __('Online') }}</span>
                                                    </button>
                                                    <button class="quick-status-option {{ $row['status'] === 'draft' ? 'is-selected' : '' }}" type="button">
                                                        <span class="quick-status-dot is-draft" aria-hidden="true"></span>
                                                        <span>{{ __('Offline') }}</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </details>
                                </div>
                                <div class="overview-item options">
                                    <a href="{{ route($routeNames['edit'], ['id' => $row['id']]) }}" title="{{ __('Bewerken') }}">
                                        <x-admin.material-icon name="edit" />
                                    </a>
                                    <form method="post" action="#" data-delete-item-name="{{ $row['title'] }}" data-delete-item-id="{{ $row['id'] }}">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" title="{{ __('Verwijderen') }}">
                                            <x-admin.material-icon name="delete" />
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
