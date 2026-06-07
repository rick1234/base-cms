@extends('layouts.admin')

@section('title', __('FAQ'))

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
                <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.faq.categories.index' : 'admin.faq.categories.index') }}">
                    <x-admin.material-icon name="folder" />
                    {{ __('Categorieen') }}
                </a>
                @if ($selectedCategoryId)
                    <a class="btn btn-toggle-child-categories" href="{{ route($routeNames['index'], array_merge(request()->except('showChild'), ['showChild' => $showChild ? 0 : 1])) }}">
                        <x-admin.material-icon name="visibility" />
                        {{ $showChild ? __('Toon alleen FAQ items in deze categorie') : __('Toon alle onderliggende FAQ items') }}
                    </a>
                @endif
            </div>

            <div class="main-section">
                @include('admin.faq.partials.page-header', [
                    'title' => __('FAQ'),
                    'section' => __('FAQ overview'),
                ])

                <div class="overview-container faq-overview-container">
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
                        <div class="overview-item question">
                            {{ __('Vraag') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'question', 'sorttype' => 'desc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'question', 'sorttype' => 'asc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
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
                            <div class="overview-item question">
                                <input name="question" type="text" value="{{ request('question', request('vraag')) }}">
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

                    @forelse ($faqItems as $faqItem)
                        <div class="overview-row">
                            <div class="overview-item id">
                                {{ $faqItem->id }}
                                @if ($selectedCategoryId && ! $showChild)
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $faqItem->id, 'direction' => 'down'])) }}">
                                        <x-admin.material-icon name="keyboard_arrow_down" />
                                    </a>
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $faqItem->id, 'direction' => 'up'])) }}">
                                        <x-admin.material-icon name="keyboard_arrow_up" />
                                    </a>
                                @endif
                            </div>
                            <div class="overview-item question">
                                {{ $faqItem->question }}
                            </div>
                            <div class="overview-item category">
                                {{ $faqItem->categories->pluck('name')->join(', ') ?: '-' }}
                            </div>
                            <div class="overview-item status">
                                <x-admin.quick-status model="faq" :record="$faqItem" :value="$faqItem->status" :active="$faqItem->isActive()" />
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $faqItem->id]) }}" title="{{ __('Bewerken') }}">
                                    <x-admin.material-icon name="edit" />
                                </a>
                                <form method="post" action="{{ route($routeNames['destroy'], $faqItem) }}">
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
                            <div class="overview-item question">{{ __('Geen FAQ items gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $faqItems])
            </div>
        </div>
    </div>
@endsection
