@extends('layouts.admin')

@section('title', __('Forms'))

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
                <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.forms.categories.index' : 'admin.forms.categories.index') }}">
                    <span class="flaticon-folder-symbol"></span>
                    {{ __('Categorieen') }}
                </a>
                @if ($selectedCategoryId)
                    <a class="btn btn-toggle-child-categories" href="{{ route($routeNames['index'], array_merge(request()->except('showChild'), ['showChild' => $showChild ? 0 : 1])) }}">
                        <span class="flaticon-visibility-button"></span>
                        {{ $showChild ? __('Toon alleen formulieren in deze categorie') : __('Toon alle onderliggende formulieren') }}
                    </a>
                @endif
            </div>

            <div class="main-section">
                @include('admin.forms.partials.page-header', [
                    'title' => __('Forms'),
                    'section' => __('Forms overview'),
                ])

                <div class="overview-container forms-overview-container">
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
                        <div class="overview-item category">{{ __('Categorie') }}</div>
                        <div class="overview-item messages">{{ __('Berichten') }}</div>
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
                                <input name="name" type="text" value="{{ request('name', request('title')) }}">
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
                            <div class="overview-item messages"></div>
                            <div class="overview-item status">
                                <select name="status">
                                    <option value="">{{ __('Selecteer') }}</option>
                                    <option value="published" @selected(request('status') === 'published' || request('status') === '1')>{{ __('Online') }}</option>
                                    <option value="draft" @selected(request('status') === 'draft' || request('status') === '0' || request('status') === '2' || request('status') === '3')>{{ __('Offline') }}</option>
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

                    @forelse ($forms as $form)
                        <div class="overview-row">
                            <div class="overview-item id">
                                {{ $form->id }}
                                @if ($selectedCategoryId && ! $showChild)
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $form->id, 'direction' => 'down'])) }}">
                                        <span class="flaticon-downwards-arrow-key"></span>
                                    </a>
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $form->id, 'direction' => 'up'])) }}">
                                        <span class="flaticon-up-arrow-key"></span>
                                    </a>
                                @endif
                            </div>
                            <div class="overview-item name">{{ $form->name }}</div>
                            <div class="overview-item category">{{ $form->categories->pluck('name')->join(', ') ?: '-' }}</div>
                            <div class="overview-item messages">
                                <a href="{{ route($routeNames['submissions'], ['id' => $form->id]) }}">{{ $form->submissions_count }}</a>
                            </div>
                            <div class="overview-item status">
                                <span class="{{ $form->isActive() ? 'active-item' : 'inactive-item' }}"></span>
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $form->id]) }}" title="{{ __('Bewerken') }}">
                                    <span class="flaticon-create-new-pencil-button"></span>
                                </a>
                                <form method="post" action="{{ route($routeNames['duplicate']) }}">
                                    @csrf
                                    <input type="hidden" name="itemId" value="{{ $form->id }}">
                                    <button type="submit" title="{{ __('Dupliceren') }}">
                                        <span class="flaticon-add-to-queue-button"></span>
                                    </button>
                                </form>
                                <form method="post" action="{{ route($routeNames['destroy'], $form) }}">
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
                            <div class="overview-item name">{{ __('Geen formulieren gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $forms])
            </div>
        </div>
    </div>
@endsection
