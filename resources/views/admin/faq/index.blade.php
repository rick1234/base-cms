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
                    <span class="flaticon-add-plus-button"></span>
                    {{ __('Toevoegen') }}
                </a>
                <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.faq.categories.index' : 'admin.faq.categories.index') }}">
                    <span class="flaticon-folder-symbol"></span>
                    {{ __('Categorieen') }}
                </a>
                @if ($selectedCategoryId)
                    <a class="btn btn-toggle-child-categories" href="{{ route($routeNames['index'], array_merge(request()->except('showChild'), ['showChild' => $showChild ? 0 : 1])) }}">
                        <span class="flaticon-visibility-button"></span>
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
                                <span class="flaticon-up-arrow-key"></span>
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'id', 'sorttype' => 'asc'])) }}">
                                <span class="flaticon-downwards-arrow-key"></span>
                            </a>
                        </div>
                        <div class="overview-item question">
                            {{ __('Vraag') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'question', 'sorttype' => 'desc'])) }}">
                                <span class="flaticon-up-arrow-key"></span>
                            </a>
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'question', 'sorttype' => 'asc'])) }}">
                                <span class="flaticon-downwards-arrow-key"></span>
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
                                <span class="flaticon-searching-magnifying-glass search-icon"></span>
                            </div>
                            <div class="overview-item question">
                                <input name="question" type="text" value="{{ request('question', request('vraag')) }}">
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

                    @forelse ($faqItems as $faqItem)
                        <div class="overview-row">
                            <div class="overview-item id">
                                {{ $faqItem->id }}
                                @if ($selectedCategoryId && ! $showChild)
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $faqItem->id, 'direction' => 'down'])) }}">
                                        <span class="flaticon-downwards-arrow-key"></span>
                                    </a>
                                    <a href="{{ route($routeNames['index'], array_merge(request()->query(), ['move' => $faqItem->id, 'direction' => 'up'])) }}">
                                        <span class="flaticon-up-arrow-key"></span>
                                    </a>
                                @endif
                            </div>
                            <div class="overview-item question">
                                {{ $faqItem->question }}
                                @if ($faqItem->images_count > 0)
                                    <span class="admin-symbol admin-symbol-gallery" aria-hidden="true"></span>
                                @endif
                                @if ($faqItem->attachments_count > 0)
                                    <span class="admin-symbol admin-symbol-attachment" aria-hidden="true"></span>
                                @endif
                            </div>
                            <div class="overview-item category">
                                {{ $faqItem->categories->pluck('name')->join(', ') ?: '-' }}
                            </div>
                            <div class="overview-item status">
                                <span class="{{ $faqItem->isActive() ? 'active-item' : 'inactive-item' }}"></span>
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $faqItem->id]) }}" title="{{ __('Bewerken') }}">
                                    <span class="flaticon-create-new-pencil-button"></span>
                                </a>
                                <form method="post" action="{{ route($routeNames['duplicate']) }}">
                                    @csrf
                                    <input type="hidden" name="itemId" value="{{ $faqItem->id }}">
                                    <button type="submit" title="{{ __('Dupliceren') }}">
                                        <span class="flaticon-add-to-queue-button"></span>
                                    </button>
                                </form>
                                <form method="post" action="{{ route($routeNames['destroy'], $faqItem) }}">
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
                            <div class="overview-item question">{{ __('Geen FAQ items gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $faqItems])
            </div>
        </div>
    </div>
@endsection
