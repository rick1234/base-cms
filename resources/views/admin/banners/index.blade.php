@extends('layouts.admin')

@section('title', __('Banner'))

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
                <a class="btn btn-add" href="{{ route($routeNames['bulk']) }}">
                    <span class="flaticon-add-plus-button"></span>
                    {{ __('Snel toevoegen') }}
                </a>
                <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.banners.categories.index' : 'admin.banners.categories.index') }}">
                    <span class="flaticon-folder-symbol"></span>
                    {{ __('Categorieen') }}
                </a>
            </div>

            <div class="main-section">
                @include('admin.banners.partials.page-header', [
                    'title' => __('Banner'),
                    'section' => __('Banner overzicht'),
                ])

                <div class="overview-container banner-overview-container">
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
                        <div class="overview-item preview">{{ __('Banner') }}</div>
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

                    <form method="get" action="{{ route($routeNames['index']) }}">
                        <div class="overview-row filters">
                            <div class="overview-item id">
                                <input name="id" type="text" value="{{ request('id') }}">
                                <span class="flaticon-searching-magnifying-glass search-icon"></span>
                            </div>
                            <div class="overview-item preview"></div>
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
                                    <option value="2" @selected(request('status', '2') === '2')>{{ __('Alles') }}</option>
                                    <option value="published" @selected(request('status') === 'published' || request('status') === '1')>{{ __('Actief') }}</option>
                                    <option value="draft" @selected(request('status') === 'draft' || request('status') === '0')>{{ __('Inactief') }}</option>
                                </select>
                            </div>
                            <div class="overview-item options">
                                <button type="submit" title="{{ __('Zoeken') }}">
                                    <span class="flaticon-searching-magnifying-glass"></span>
                                </button>
                            </div>
                        </div>
                    </form>

                    @forelse ($banners as $banner)
                        <div class="overview-row">
                            <div class="overview-item id">
                                @if ($selectedCategoryId)
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $banner->id, 'direction' => 'down'])) }}">
                                        <span class="flaticon-downwards-arrow-key"></span>
                                    </a>
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $banner->id, 'direction' => 'up'])) }}">
                                        <span class="flaticon-up-arrow-key"></span>
                                    </a>
                                @endif
                                {{ $banner->id }}
                            </div>
                            <div class="overview-item preview">
                                @if ($banner->image_path)
                                    <a href="{{ asset($banner->image_path) }}" target="_blank" rel="noreferrer">
                                        <img src="{{ asset($banner->image_path) }}" alt="{{ $banner->metadata['alt_text'] ?? __('Banner voorbeeld') }}">
                                    </a>
                                @else
                                    <span class="admin-symbol admin-symbol-image-missing" aria-hidden="true"></span>
                                @endif
                            </div>
                            <div class="overview-item category">{{ $banner->categories->pluck('name')->join(', ') ?: '-' }}</div>
                            <div class="overview-item title">{{ $banner->displayTitle() }}</div>
                            <div class="overview-item status">
                                <span class="{{ $banner->isActive() ? 'active-item' : 'inactive-item' }}"></span>
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $banner->id]) }}" title="{{ __('Bewerken') }}">
                                    <span class="flaticon-create-new-pencil-button"></span>
                                </a>
                                <form method="post" action="{{ route($routeNames['duplicate']) }}">
                                    @csrf
                                    <input type="hidden" name="itemId" value="{{ $banner->id }}">
                                    <button type="submit" title="{{ __('Dupliceren') }}">
                                        <span class="flaticon-add-to-queue-button"></span>
                                    </button>
                                </form>
                                <form method="post" action="{{ route($routeNames['destroy'], $banner) }}">
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
                            <div class="overview-item title">{{ __('Geen banners gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $banners])
            </div>
        </div>
    </div>
@endsection
