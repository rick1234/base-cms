@extends('layouts.admin')

@section('title', __('Events'))

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
                <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.events.categories.index' : 'admin.events.categories.index') }}">
                    <x-admin.material-icon name="folder" />
                    {{ __('Categorieen') }}
                </a>
                @if ($selectedCategoryId)
                    <a class="btn btn-toggle-child-categories" href="{{ route($routeNames['index'], array_merge(request()->except('showChild'), ['showChild' => $showChild ? 0 : 1])) }}">
                        <x-admin.material-icon name="visibility" />
                        {{ $showChild ? __('Toon alleen evenementen in deze categorie') : __('Toon alle onderliggende evenementen') }}
                    </a>
                @endif
            </div>

            <div class="main-section">
                @include('admin.events.partials.page-header', [
                    'title' => __('Events'),
                    'section' => __('Evenementen overzicht'),
                ])

                <div class="overview-container events-overview-container">
                    <div class="overview-row header">
                        <div class="overview-item id">
                            {{ __('ID') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'id', 'sorttype' => 'desc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'id', 'sorttype' => 'asc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
                        <div class="overview-item date">{{ __('Datum') }}</div>
                        <div class="overview-item title">
                            {{ __('Titel') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'title', 'sorttype' => 'desc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'title', 'sorttype' => 'asc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
                        <div class="overview-item category">{{ __('Categorie') }}</div>
                        <div class="overview-item status">{{ __('Status') }}</div>
                        <div class="overview-item options">{{ __('Opties') }}</div>
                    </div>

                    <form id="event-filter" method="get" action="{{ route($routeNames['index']) }}">
                        <div class="overview-row filters">
                            <div class="overview-item id">
                                <input name="id" type="text" value="{{ request('id') }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item date"></div>
                            <div class="overview-item title">
                                <input name="title" type="text" value="{{ request('title', request('titel')) }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item category">
                                @include('admin.partials.listing-category-filter', [
                                    'categories' => $categories,
                                    'selectedCategoryId' => $selectedCategoryId,
                                    'showChild' => $showChild,
                                    'clearUrl' => route($routeNames['index'], request()->except(['categoryId', 'showChild'])),
                                ])
                            </div>
                            <div class="overview-item status">
                                <select name="status">
                                    <option value="">{{ __('Selecteer') }}</option>
                                    <option value="published" @selected(request('status') === 'published' || request('status') === '1')>{{ __('Online') }}</option>
                                    <option value="draft" @selected(request('status') === 'draft' || request('status') === '0')>{{ __('Offline') }}</option>
                                    <option value="archived" @selected(request('status') === 'archived')>{{ __('Archived') }}</option>
                                </select>
                            </div>
                            <div class="overview-item options">
                                <button type="submit" title="{{ __('Zoeken') }}">
                                    <x-admin.material-icon name="search" />
                                </button>
                            </div>
                        </div>
                    </form>

                    @forelse ($events as $event)
                        <div class="overview-row">
                            <div class="overview-item id">
                                {{ $event->id }}
                                @if ($selectedCategoryId && ! $showChild)
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $event->id, 'direction' => 'down'])) }}">
                                        <x-admin.material-icon name="keyboard_arrow_down" />
                                    </a>
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $event->id, 'direction' => 'up'])) }}">
                                        <x-admin.material-icon name="keyboard_arrow_up" />
                                    </a>
                                @endif
                            </div>
                            <div class="overview-item date">
                                {{ optional($event->starts_at)->format('d-m-Y') ?: '-' }}
                            </div>
                            <div class="overview-item title">
                                {{ $event->title }}
                            </div>
                            <div class="overview-item category">
                                {{ $event->categories->pluck('name')->join(', ') ?: '-' }}
                            </div>
                            <div class="overview-item status">
                                <x-admin.quick-status model="event" :record="$event" :value="$event->status" :active="$event->isActive()" />
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $event->id]) }}" title="{{ __('Bewerken') }}">
                                    <x-admin.material-icon name="edit" />
                                </a>
                                <form method="post" action="{{ route($routeNames['destroy'], $event) }}">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" title="{{ __('Verwijderen') }}">
                                        <x-admin.material-icon name="delete" />
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="overview-row">
                            <div class="overview-item title">{{ __('Geen evenementen gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $events])
            </div>
        </div>
    </div>
@endsection
