@extends('layouts.admin')

@section('title', __('Locations'))

@section('body')
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
                <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.locations.categories.index' : 'admin.locations.categories.index') }}">
                    <x-admin.material-icon name="folder" />
                    {{ __('Categorieen') }}
                </a>
                @if ($selectedCategoryId)
                    <a class="btn btn-toggle-child-categories" href="{{ route($routeNames['index'], array_merge(request()->except('showChild'), ['showChild' => $showChild ? 0 : 1])) }}">
                        <x-admin.material-icon name="visibility" />
                        {{ $showChild ? __('Toon alleen vestigingen in deze categorie') : __('Toon alle onderliggende vestigingen') }}
                    </a>
                @endif
            </div>

            <div class="main-section">
                @include('admin.locations.partials.page-header', [
                    'title' => __('Locations'),
                    'section' => __('Location overview'),
                ])

                <span class="content-admin-screen-label">{{ __('Location overview') }}</span>

                <div class="overview-container locations-overview-container">
                    <div class="overview-row header">
                        <div class="overview-item id">
                            Id
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'id', 'sorttype' => 'desc'])) }}" aria-label="{{ __('Sorteer aflopend') }}" title="{{ __('Sorteer aflopend') }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'id', 'sorttype' => 'asc'])) }}" aria-label="{{ __('Sorteer oplopend') }}" title="{{ __('Sorteer oplopend') }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
                        <div class="overview-item name">
                            {{ __('Naam') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'name', 'sorttype' => 'desc'])) }}" aria-label="{{ __('Sorteer aflopend') }}" title="{{ __('Sorteer aflopend') }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'name', 'sorttype' => 'asc'])) }}" aria-label="{{ __('Sorteer oplopend') }}" title="{{ __('Sorteer oplopend') }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
                        <div class="overview-item city">{{ __('Plaats') }}</div>
                        <div class="overview-item category">{{ __('Categorie') }}</div>
                        <div class="overview-item status">{{ __('Status') }}</div>
                        <div class="overview-item options">{{ __('Opties') }}</div>
                    </div>

                    <form method="get" action="{{ route($routeNames['index']) }}">
                        <div class="overview-row filters">
                            <div class="overview-item id">
                                <input name="id" type="text" value="{{ request('id') }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item name">
                                <input name="name" type="text" value="{{ request('name', request('naam')) }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item city">
                                <input name="city" type="text" value="{{ request('city', request('plaats')) }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item category">
                                <select name="categoryId">
                                    <option value="0">{{ __('Selecteer') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected($selectedCategoryId === $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="overview-item status">
                                <select name="status">
                                    <option value="">{{ __('Selecteer') }}</option>
                                    <option value="active" @selected(in_array(request('status'), ['active', '1'], true))>{{ __('Online') }}</option>
                                    <option value="inactive" @selected(in_array(request('status'), ['inactive', '0', '2', '3'], true))>{{ __('Offline') }}</option>
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

                    @forelse ($locations as $location)
                        <div class="overview-row">
                            <div class="overview-item id">
                                {{ $location->id }}
                                @if ($selectedCategoryId && ! $showChild)
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $location->id, 'direction' => 'down'])) }}" aria-label="{{ __('Omlaag verplaatsen') }}" title="{{ __('Omlaag verplaatsen') }}">
                                        <x-admin.material-icon name="keyboard_arrow_down" />
                                    </a>
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $location->id, 'direction' => 'up'])) }}" aria-label="{{ __('Omhoog verplaatsen') }}" title="{{ __('Omhoog verplaatsen') }}">
                                        <x-admin.material-icon name="keyboard_arrow_up" />
                                    </a>
                                @endif
                            </div>
                            <div class="overview-item name">
                                {{ $location->name }}
                                @if ($location->images_count > 0)
                                    <x-admin.material-icon name="photo_library" :label="__('Heeft afbeeldingen')" title="{{ __('Heeft afbeeldingen') }}" />
                                @endif
                                @if ($location->opening_hours_count > 0 || $location->special_opening_hours_count > 0)
                                    <x-admin.material-icon name="schedule" :label="__('Heeft openingstijden')" title="{{ __('Heeft openingstijden') }}" />
                                @endif
                            </div>
                            <div class="overview-item city">{{ $location->city ?: '-' }}</div>
                            <div class="overview-item category">
                                {{ $location->categories->pluck('name')->join(', ') ?: '-' }}
                            </div>
                            <div class="overview-item status">
                                <span class="{{ $location->isActive() ? 'active-item' : 'inactive-item' }}" role="img" aria-label="{{ $location->isActive() ? __('Online') : __('Offline') }}" title="{{ $location->isActive() ? __('Online') : __('Offline') }}"></span>
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $location->id]) }}" aria-label="{{ __('Bewerken') }}" title="{{ __('Bewerken') }}">
                                    <x-admin.material-icon name="edit" />
                                </a>
                                <form method="post" action="{{ route($routeNames['destroy'], $location) }}">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" aria-label="{{ __('Verwijderen') }}" title="{{ __('Verwijderen') }}">
                                        <x-admin.material-icon name="delete" />
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="overview-row">
                            <div class="overview-item name">{{ __('Geen vestigingen gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $locations])
            </div>
        </div>
    </div>
@endsection
