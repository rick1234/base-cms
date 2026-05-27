@extends('layouts.admin')

@section('title', __('Events'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container">
                <div class="buttons-container align-right">
                    <a class="btn btn-add" href="{{ route($routeNames['create']) }}">
                        <span class="flaticon-add-plus-button"></span>
                        {{ __('Toevoegen') }}
                    </a>
                    <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.events.categories.index' : 'admin.events.categories.index') }}">
                        <span class="flaticon-folder-symbol"></span>
                        {{ __('Categorieen') }}
                    </a>
                    @if ($selectedCategoryId)
                        <a class="btn btn-toggle-child-categories" href="{{ route($routeNames['index'], array_merge(request()->except('showChild'), ['showChild' => $showChild ? 0 : 1])) }}">
                            <span class="flaticon-visibility-button"></span>
                            {{ $showChild ? __('Toon alleen evenementen in deze categorie') : __('Toon alle onderliggende evenementen') }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="main-section">
                @include('admin.events.partials.page-header', [
                    'title' => __('Events'),
                    'section' => __('Evenementen overzicht'),
                ])

                <div class="overview-container events-overview-container">
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
                        <div class="overview-item date">{{ __('Datum') }}</div>
                        <div class="overview-item category">{{ __('Categorie') }}</div>
                        <div class="overview-item title">
                            {{ __('Titel') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'title', 'sorttype' => 'desc'])) }}">
                                <span class="flaticon-up-arrow-key"></span>
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'title', 'sorttype' => 'asc'])) }}">
                                <span class="flaticon-downwards-arrow-key"></span>
                            </a>
                        </div>
                        <div class="overview-item status">{{ __('Status') }}</div>
                        <div class="overview-item options">{{ __('Opties') }}</div>
                    </div>

                    <form id="event-filter" method="get" action="{{ route($routeNames['index']) }}">
                        <div class="overview-row filters">
                            <div class="overview-item id">
                                <input name="id" type="text" value="{{ request('id') }}">
                                <span class="flaticon-searching-magnifying-glass search-icon"></span>
                            </div>
                            <div class="overview-item date"></div>
                            <div class="overview-item category">
                                <select name="categoryId">
                                    <option value="0">{{ __('Selecteer') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected($selectedCategoryId === $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="overview-item title">
                                <input name="title" type="text" value="{{ request('title', request('titel')) }}">
                                <span class="flaticon-searching-magnifying-glass search-icon"></span>
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
                                <input type="hidden" name="showChild" value="{{ $showChild ? 1 : 0 }}">
                                <button type="submit" title="{{ __('Zoeken') }}">
                                    <span class="flaticon-searching-magnifying-glass"></span>
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
                                        <span class="flaticon-downwards-arrow-key"></span>
                                    </a>
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $event->id, 'direction' => 'up'])) }}">
                                        <span class="flaticon-up-arrow-key"></span>
                                    </a>
                                @endif
                            </div>
                            <div class="overview-item date">
                                {{ optional($event->starts_at)->format('d-m-Y') ?: '-' }}
                                @if ($event->attachments_count > 0)
                                    <span class="admin-symbol admin-symbol-attachment" aria-hidden="true"></span>
                                @endif
                            </div>
                            <div class="overview-item category">
                                {{ $event->categories->pluck('name')->join(', ') ?: '-' }}
                            </div>
                            <div class="overview-item title">
                                {{ $event->title }}
                            </div>
                            <div class="overview-item status">
                                <span class="{{ $event->isActive() ? 'active-item' : 'inactive-item' }}"></span>
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $event->id]) }}" title="{{ __('Bewerken') }}">
                                    <span class="flaticon-create-new-pencil-button"></span>
                                </a>
                                <form method="post" action="{{ route($routeNames['duplicate']) }}">
                                    @csrf
                                    <input type="hidden" name="itemId" value="{{ $event->id }}">
                                    <button type="submit" title="{{ __('Dupliceren') }}">
                                        <span class="flaticon-add-to-queue-button"></span>
                                    </button>
                                </form>
                                <form method="post" action="{{ route($routeNames['destroy'], $event) }}">
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
                            <div class="overview-item title">{{ __('Geen evenementen gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $events])
            </div>
        </div>
    </div>
@endsection
