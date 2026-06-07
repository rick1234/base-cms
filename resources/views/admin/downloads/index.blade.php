@extends('layouts.admin')

@section('title', __('Downloads'))

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
                <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.downloads.categories.index' : 'admin.downloads.categories.index') }}">
                    <x-admin.material-icon name="folder" />
                    {{ __('Categorieen') }}
                </a>
                @if ($selectedCategoryId)
                    <a class="btn btn-toggle-child-categories" href="{{ route($routeNames['index'], array_merge(request()->except('showChild'), ['showChild' => $showChild ? 0 : 1])) }}">
                        <x-admin.material-icon name="visibility" />
                        {{ $showChild ? __('Toon alleen downloads in deze categorie') : __('Toon alle onderliggende downloads') }}
                    </a>
                @endif
            </div>

            <div class="main-section">
                @include('admin.downloads.partials.page-header', [
                    'title' => __('Downloads'),
                    'section' => __('Download overview'),
                ])

                <span class="content-admin-screen-label">{{ __('Download overview') }}</span>

                <div class="overview-container downloads-overview-container">
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
                        <div class="overview-item name">
                            {{ __('Naam') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'name', 'sorttype' => 'desc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'name', 'sorttype' => 'asc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
                        <div class="overview-item category">{{ __('Categorie') }}</div>
                        <div class="overview-item downloads">{{ __('Downloads') }}</div>
                        <div class="overview-item file">{{ __('Bestand') }}</div>
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
                            <div class="overview-item category">
                                <select name="categoryId">
                                    <option value="0">{{ __('Selecteer') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected($selectedCategoryId === $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="overview-item downloads"></div>
                            <div class="overview-item file"></div>
                            <div class="overview-item status">
                                <select name="status">
                                    <option value="">{{ __('Kies een status') }}</option>
                                    <option value="active" @selected(in_array(request('status', request('actief')), ['active', '1'], true))>{{ __('Actief') }}</option>
                                    <option value="inactive" @selected(in_array(request('status', request('actief')), ['inactive', '0'], true))>{{ __('Inactief') }}</option>
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

                    @forelse ($downloads as $download)
                        <div class="overview-row">
                            <div class="overview-item id">{{ $download->id }}</div>
                            <div class="overview-item name">
                                {{ $download->name }}
                                @if ($download->is_password_protected)
                                    <x-admin.material-icon name="lock" />
                                @endif
                            </div>
                            <div class="overview-item category">
                                {{ $download->categories->pluck('name')->join(', ') ?: '-' }}
                            </div>
                            <div class="overview-item downloads">{{ $download->download_count }}</div>
                            <div class="overview-item file">{{ $download->original_filename ?: '-' }}</div>
                            <div class="overview-item status">
                                <x-admin.quick-status model="download" :record="$download" :value="$download->status" :active="$download->isActive()" />
                            </div>
                            <div class="overview-item options">
                                @if ($download->slug && $download->hasFile())
                                    <a href="{{ route('frontend.downloads.show', ['download' => $download->publicRouteKey()]) }}" title="{{ __('Download bestand') }}" target="_blank">
                                        <x-admin.material-icon name="download" />
                                    </a>
                                @endif
                                <a href="{{ route($routeNames['edit'], ['id' => $download->id]) }}" title="{{ __('Bewerken') }}">
                                    <x-admin.material-icon name="edit" />
                                </a>
                                <form method="post" action="{{ route($routeNames['destroy'], $download) }}">
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
                            <div class="overview-item name">{{ __('Geen downloads gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $downloads])
            </div>
        </div>
    </div>
@endsection
