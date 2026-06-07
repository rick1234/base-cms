@extends('layouts.admin')

@section('title', __('Users'))

@section('body')
    @php
        $categoriesByParent = $categories->groupBy(fn ($category) => (int) ($category->parent_id ?? 0));
        $selectedCategory = $categories->firstWhere('id', $selectedCategoryId);
    @endphp

    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <a class="btn btn-add" href="{{ route($routeNames['create']) }}">
                    <x-admin.material-icon name="add" />
                    {{ __('Toevoegen') }}
                </a>
                <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.users.categories.index' : 'admin.users.categories.index') }}">
                    <x-admin.material-icon name="folder" />
                    {{ __('Categorieen') }}
                </a>
                @if ($selectedCategoryId)
                    <a class="btn btn-toggle-child-categories" href="{{ route($routeNames['index'], array_merge(request()->except('showChild'), ['showChild' => $showChild ? 0 : 1])) }}">
                        <x-admin.material-icon name="visibility" />
                        {{ $showChild ? __('Toon alleen gebruikers in deze categorie') : __('Toon alle onderliggende gebruikers') }}
                    </a>
                @endif
            </div>

            <div class="main-section">
                @include('admin.users.partials.page-header', [
                    'title' => __('Users'),
                    'section' => __('Gebruikers overzicht'),
                ])

                <div class="overview-container user-overview-container">
                    <div class="overview-row header">
                        <div class="overview-item id">
                            Id
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'id', 'sorttype' => 'desc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'id', 'sorttype' => 'asc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
                        <div class="overview-item user">
                            {{ __('Gebruiker') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'username', 'sorttype' => 'desc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'username', 'sorttype' => 'asc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
                        <div class="overview-item name">
                            {{ __('Name') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'name', 'sorttype' => 'desc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'name', 'sorttype' => 'asc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
                        <div class="overview-item category">{{ __('Categorie') }}</div>
                        <div class="overview-item last-login">
                            {{ __('Last login') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'last_login', 'sorttype' => 'desc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'last_login', 'sorttype' => 'asc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
                        <div class="overview-item status">{{ __('Status') }}</div>
                        <div class="overview-item options">{{ __('Opties') }}</div>
                    </div>

                    <form method="get" action="{{ route($routeNames['index']) }}">
                        <div class="overview-row filters">
                            <div class="overview-item id">
                                <input name="id" type="text" value="{{ request('id') }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item user">
                                <input name="username" type="text" value="{{ request('username') }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item name">
                                <input name="name" type="text" value="{{ request('name') }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item category">
                                <div class="listing-category-picker">
                                    <details class="listing-category-native">
                                        <summary class="listing-category-picker-button">
                                            <x-admin.material-icon name="folder" />
                                            <span>{{ $selectedCategory?->name ?? __('All categories') }}</span>
                                        </summary>
                                        <div class="listing-category-native-panel">
                                            <div class="listing-category-modal-header">
                                                <h2>{{ __('Select category') }}</h2>
                                            </div>
                                            <div class="listing-category-modal-body">
                                                <div class="listing-category-modal-options">
                                                    <label class="listing-category-option {{ $selectedCategoryId === 0 ? 'is-selected' : '' }}">
                                                        <input type="radio" name="categoryId" value="0" @checked($selectedCategoryId === 0)>
                                                        <x-admin.material-icon name="folder_open" />
                                                        <span>{{ __('All categories') }}</span>
                                                    </label>
                                                </div>

                                                @include('admin.partials.category-filter-tree', [
                                                    'categoriesByParent' => $categoriesByParent,
                                                    'parentId' => 0,
                                                    'selectedCategoryId' => $selectedCategoryId,
                                                    'mode' => 'radio',
                                                    'inputName' => 'categoryId',
                                                ])
                                            </div>
                                        </div>
                                    </details>

                                    @if ($selectedCategoryId)
                                        <a class="listing-category-clear-button" href="{{ route($routeNames['index'], request()->except(['categoryId', 'showChild'])) }}" title="{{ __('Clear category') }}">
                                            <x-admin.material-icon name="close" />
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="overview-item last-login">
                                <span class="overview-filter-placeholder">{{ __('Date') }}</span>
                            </div>
                            <div class="overview-item status">
                                <select name="status">
                                    <option value="">{{ __('Selecteer') }}</option>
                                    <option value="1" @selected(request('status') === '1')>{{ __('Alles') }}</option>
                                    <option value="2" @selected(request('status') === '2' || request('status') === 'active')>{{ __('Actief') }}</option>
                                    <option value="3" @selected(request('status') === '3' || request('status') === 'inactive')>{{ __('Inactief') }}</option>
                                    <option value="4" @selected(request('status') === '4' || request('status') === 'revoked')>{{ __('Revoked') }}</option>
                                </select>
                            </div>
                            <div class="overview-item options">
                                <input type="hidden" name="showChild" value="{{ $showChild ? 1 : 0 }}">
                                <button type="submit" title="{{ __('Zoeken') }}">
                                    <x-admin.material-icon name="search" />
                                </button>
                            </div>
                        </div>
                    </form>

                    @forelse ($users as $listedUser)
                        <div class="overview-row">
                            <div class="overview-item id">{{ $listedUser->id }}</div>
                            <div class="overview-item user">
                                {{ $listedUser->displayName() }}
                                <small>{{ $listedUser->email }}</small>
                            </div>
                            <div class="overview-item name">
                                {{ $listedUser->fullName() }}
                            </div>
                            <div class="overview-item category">
                                {{ $listedUser->categories->pluck('name')->join(', ') ?: '-' }}
                            </div>
                            <div class="overview-item last-login">
                                {{ $listedUser->last_login_at?->format('d-m-Y H:i') ?? __('Never') }}
                            </div>
                            <div class="overview-item status">
                                <x-admin.quick-status model="user" :record="$listedUser" :value="$listedUser->is_active" :active="$listedUser->isActive()" />
                                @if ($listedUser->isRevoked())
                                    <span class="status-note is-revoked">{{ __('Revoked') }}</span>
                                @endif
                            </div>
                            <div class="overview-item options">
                                @unless (auth()->user()?->is($listedUser))
                                    <form method="post" action="{{ route($routeNames['impersonate'], $listedUser) }}">
                                        @csrf
                                        <button type="submit" title="{{ __('Log in as this user') }}">
                                            <x-admin.material-icon name="login" />
                                        </button>
                                    </form>
                                @endunless
                                <a href="{{ route($routeNames['edit'], ['id' => $listedUser->id]) }}" title="{{ __('Bewerken') }}">
                                    <x-admin.material-icon name="edit" />
                                </a>
                                @unless (auth()->user()?->is($listedUser))
                                    <form method="post" action="{{ route($routeNames['destroy'], $listedUser) }}">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" title="{{ __('Verwijderen') }}">
                                            <x-admin.material-icon name="delete" />
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    @empty
                        <div class="overview-row">
                            <div class="overview-item user">{{ __('Geen gebruikers gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $users])
            </div>
        </div>
    </div>
@endsection
