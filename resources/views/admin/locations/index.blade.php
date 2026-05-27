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
                    <span class="flaticon-add-plus-button"></span>
                    {{ __('Toevoegen') }}
                </a>
                <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.locations.categories.index' : 'admin.locations.categories.index') }}">
                    <span class="flaticon-folder-symbol"></span>
                    {{ __('Categorieen') }}
                </a>
                @if ($selectedCategoryId)
                    <a class="btn btn-toggle-child-categories" href="{{ route($routeNames['index'], array_merge(request()->except('showChild'), ['showChild' => $showChild ? 0 : 1])) }}">
                        <span class="flaticon-visibility-button"></span>
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
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'id', 'sorttype' => 'desc'])) }}">
                                <span class="flaticon-up-arrow-key"></span>
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'id', 'sorttype' => 'asc'])) }}">
                                <span class="flaticon-downwards-arrow-key"></span>
                            </a>
                        </div>
                        <div class="overview-item name">
                            {{ __('Naam') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'name', 'sorttype' => 'desc'])) }}">
                                <span class="flaticon-up-arrow-key"></span>
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'name', 'sorttype' => 'asc'])) }}">
                                <span class="flaticon-downwards-arrow-key"></span>
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
                                <span class="flaticon-searching-magnifying-glass search-icon"></span>
                            </div>
                            <div class="overview-item name">
                                <input name="name" type="text" value="{{ request('name', request('naam')) }}">
                                <span class="flaticon-searching-magnifying-glass search-icon"></span>
                            </div>
                            <div class="overview-item city">
                                <input name="city" type="text" value="{{ request('city', request('plaats')) }}">
                                <span class="flaticon-searching-magnifying-glass search-icon"></span>
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
                                    <span class="flaticon-searching-magnifying-glass"></span>
                                </button>
                            </div>
                        </div>
                    </form>

                    @forelse ($locations as $location)
                        <div class="overview-row">
                            <div class="overview-item id">
                                {{ $location->id }}
                                @if ($selectedCategoryId && ! $showChild)
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $location->id, 'direction' => 'down'])) }}">
                                        <span class="flaticon-downwards-arrow-key"></span>
                                    </a>
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $location->id, 'direction' => 'up'])) }}">
                                        <span class="flaticon-up-arrow-key"></span>
                                    </a>
                                @endif
                            </div>
                            <div class="overview-item name">
                                {{ $location->name }}
                                @if ($location->images_count > 0)
                                    <span class="admin-symbol admin-symbol-gallery" aria-hidden="true"></span>
                                @endif
                                @if ($location->opening_hours_count > 0 || $location->special_opening_hours_count > 0)
                                    <span class="admin-symbol admin-symbol-schedule" aria-hidden="true"></span>
                                @endif
                            </div>
                            <div class="overview-item city">{{ $location->city ?: '-' }}</div>
                            <div class="overview-item category">
                                {{ $location->categories->pluck('name')->join(', ') ?: '-' }}
                            </div>
                            <div class="overview-item status">
                                <span class="{{ $location->isActive() ? 'active-item' : 'inactive-item' }}"></span>
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $location->id]) }}" title="{{ __('Bewerken') }}">
                                    <span class="flaticon-create-new-pencil-button"></span>
                                </a>
                                <form method="post" action="{{ route($routeNames['duplicate']) }}">
                                    @csrf
                                    <input type="hidden" name="itemId" value="{{ $location->id }}">
                                    <button type="submit" title="{{ __('Dupliceren') }}">
                                        <span class="flaticon-add-to-queue-button"></span>
                                    </button>
                                </form>
                                <form method="post" action="{{ route($routeNames['destroy'], $location) }}">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" title="{{ __('Verwijderen') }}">
                                        <span class="flaticon-rubbish-bin-delete-button"></span>
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
