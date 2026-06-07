@extends('layouts.admin')

@section('title', __('Vacancies'))

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
                <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.vacancies.categories.index' : 'admin.vacancies.categories.index') }}">
                    <x-admin.material-icon name="folder" />
                    {{ __('Categorieen') }}
                </a>
                @if ($selectedCategoryId)
                    <a class="btn btn-toggle-child-categories" href="{{ route($routeNames['index'], array_merge(request()->except('showChild'), ['showChild' => $showChild ? 0 : 1])) }}">
                        <x-admin.material-icon name="visibility" />
                        {{ $showChild ? __('Toon alleen vacatures in deze categorie') : __('Toon alle onderliggende vacatures') }}
                    </a>
                @endif
            </div>

            <div class="main-section">
                @include('admin.vacancies.partials.page-header', [
                    'title' => __('Vacancies'),
                    'section' => __('Vacancy overview'),
                ])

                <span class="content-admin-screen-label">{{ __('Vacancy overview') }}</span>

                <div class="overview-container vacancies-overview-container">
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
                        <div class="overview-item title">
                            {{ __('Titel') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'title', 'sorttype' => 'desc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'title', 'sorttype' => 'asc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
                        <div class="overview-item language">{{ __('Taal') }}</div>
                        <div class="overview-item category">{{ __('Categorie') }}</div>
                        <div class="overview-item reactions">{{ __('Reacties') }}</div>
                        <div class="overview-item status">{{ __('Status') }}</div>
                        <div class="overview-item options">{{ __('Opties') }}</div>
                    </div>

                    <form method="get" action="{{ route($routeNames['index']) }}">
                        <div class="overview-row filters">
                            <div class="overview-item id">
                                <input name="id" type="text" value="{{ request('id') }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item title">
                                <input name="title" type="text" value="{{ request('title', request('titel', request('naam'))) }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item language">
                                <select name="locale">
                                    <option value="">{{ __('Taal') }}</option>
                                    <option value="nl" @selected(request('locale', request('taalcode')) === 'nl')>{{ __('Dutch') }}</option>
                                    <option value="en" @selected(request('locale', request('taalcode')) === 'en')>{{ __('English') }}</option>
                                </select>
                            </div>
                            <div class="overview-item category">
                                @include('admin.partials.listing-category-filter', [
                                    'categories' => $categories,
                                    'selectedCategoryId' => $selectedCategoryId,
                                    'showChild' => $showChild,
                                    'clearUrl' => route($routeNames['index'], request()->except(['categoryId', 'showChild'])),
                                ])
                            </div>
                            <div class="overview-item reactions"></div>
                            <div class="overview-item status">
                                <select name="status">
                                    <option value="">{{ __('Selecteer') }}</option>
                                    <option value="published" @selected(request('status') === 'published' || request('status') === '1')>{{ __('Online') }}</option>
                                    <option value="draft" @selected(request('status') === 'draft' || request('status') === '0' || request('status') === '2' || request('status') === '3')>{{ __('Offline') }}</option>
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

                    @forelse ($vacancies as $vacancy)
                        <div class="overview-row">
                            <div class="overview-item id">{{ $vacancy->id }}</div>
                            <div class="overview-item title">{{ $vacancy->title }}</div>
                            <div class="overview-item language">
                                <x-admin.language-flag :locale="$vacancy->locale ?: app()->getLocale()" :label="strtoupper($vacancy->locale ?: app()->getLocale())" />
                            </div>
                            <div class="overview-item category">
                                {{ $vacancy->categories->pluck('name')->join(', ') ?: '-' }}
                            </div>
                            <div class="overview-item reactions">
                                @php
                                    $reactionCount = (int) ($vacancy->form?->submissions_count ?? 0);
                                    $formSubmissionsRouteName = request()->routeIs('cms.*') ? 'cms.forms.submissions' : 'admin.forms.submissions';
                                @endphp

                                @if ($vacancy->form && $reactionCount > 0)
                                    <a class="overview-count-link" href="{{ route($formSubmissionsRouteName, ['id' => $vacancy->form->id]) }}">
                                        {{ $reactionCount }}
                                    </a>
                                @else
                                    <span class="overview-muted-count">{{ $reactionCount }}</span>
                                @endif
                            </div>
                            <div class="overview-item status">
                                <x-admin.quick-status model="vacancy" :record="$vacancy" :value="$vacancy->status" :active="$vacancy->isActive()" />
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $vacancy->id]) }}" title="{{ __('Bewerken') }}">
                                    <x-admin.material-icon name="edit" />
                                </a>
                                <form method="post" action="{{ route($routeNames['destroy'], $vacancy) }}">
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
                            <div class="overview-item title">{{ __('Geen vacatures gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $vacancies])
            </div>
        </div>
    </div>
@endsection
