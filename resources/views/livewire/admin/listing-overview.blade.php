<div class="listing-overview" wire:loading.class="is-loading">
    <div class="overview-container content-overview-container listing-overview-container">
        <div class="overview-row header">
            <div class="overview-item id">
                Id
                <button type="button" wire:click="sortBy('id', 'desc')" title="{{ __('Nieuwste eerst') }}">
                    <span class="flaticon-up-arrow-key"></span>
                </button>
                <button type="button" wire:click="sortBy('id', 'asc')" title="{{ __('Oudste eerst') }}">
                    <span class="flaticon-downwards-arrow-key"></span>
                </button>
            </div>
            <div class="overview-item language">{{ __('Taal') }}</div>
            <div class="overview-item category">{{ __('Categorie') }}</div>
            <div class="overview-item title">
                {{ __('Titel') }}
                <button type="button" wire:click="sortBy('title', 'desc')" title="{{ __('Sorteer aflopend') }}">
                    <span class="flaticon-up-arrow-key"></span>
                </button>
                <button type="button" wire:click="sortBy('title', 'asc')" title="{{ __('Sorteer oplopend') }}">
                    <span class="flaticon-downwards-arrow-key"></span>
                </button>
                <span class="listing-sort-state" aria-live="polite">
                    @if ($sort === 'title')
                        {{ strtoupper($direction) }}
                    @endif
                </span>
            </div>
            <div class="overview-item status">{{ __('Status') }}</div>
            <div class="overview-item options">{{ __('Opties') }}</div>
        </div>

        <form class="overview-row filters" wire:submit.prevent="applyFilters">
            <div class="overview-item id">
                <input name="id" type="text" wire:model="draftId">
                <span class="flaticon-searching-magnifying-glass search-icon"></span>
            </div>
            <div class="overview-item language">
                <select name="locale" wire:model="draftLocale">
                    <option value="">{{ __('Selecteer') }}</option>
                    @foreach ($localeOptions as $locale)
                        <option value="{{ $locale }}">{{ strtoupper($locale) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="overview-item category">
                <div class="listing-category-picker">
                    <button class="listing-category-picker-button" type="button" wire:click="openCategorySelector">
                        <span class="admin-symbol admin-symbol-folder" aria-hidden="true"></span>
                        <span>{{ $selectedCategoryName }}</span>
                    </button>
                    @if ($draftCategoryId > 0)
                        <button class="listing-category-clear-button" type="button" wire:click="clearCategory" title="{{ __('Categorie wissen') }}">
                            <span class="flaticon-close-button"></span>
                        </button>
                    @endif
                </div>
            </div>
            <div class="overview-item title">
                <input name="title" type="text" wire:model="draftTitle">
                <span class="flaticon-searching-magnifying-glass search-icon"></span>
            </div>
            <div class="overview-item status">
                <select name="status" wire:model="draftStatus">
                    <option value="">{{ __('Selecteer') }}</option>
                    <option value="published">{{ __('Online') }}</option>
                    <option value="draft">{{ __('Offline') }}</option>
                    <option value="archived">{{ __('Archived') }}</option>
                </select>
            </div>
            <div class="overview-item options">
                <button type="submit" title="{{ __('Zoeken') }}" wire:loading.attr="disabled">
                    <span class="flaticon-searching-magnifying-glass"></span>
                </button>
                <button type="button" title="{{ __('Reset') }}" wire:click="resetFilters" wire:loading.attr="disabled">
                    <span class="flaticon-close-button"></span>
                </button>
            </div>
        </form>

        @forelse ($items as $contentItem)
            <div class="overview-row" wire:key="content-listing-row-{{ $contentItem->id }}">
                <div class="overview-item id">
                    {{ $contentItem->id }}
                    @if ($filterCategoryId && ! $showChild)
                        <button type="button" wire:click="moveItem({{ $contentItem->id }}, 'down')" title="{{ __('Omlaag') }}">
                            <span class="flaticon-downwards-arrow-key"></span>
                        </button>
                        <button type="button" wire:click="moveItem({{ $contentItem->id }}, 'up')" title="{{ __('Omhoog') }}">
                            <span class="flaticon-up-arrow-key"></span>
                        </button>
                    @endif
                </div>
                <div class="overview-item language">
                    <x-admin.language-flag :locale="$contentItem->locale ?? app()->getLocale()" />
                    @if ($contentItem->attachments_count > 0)
                        <span class="admin-symbol admin-symbol-attachment" aria-hidden="true"></span>
                    @endif
                </div>
                <div class="overview-item category">
                    {{ $contentItem->categories->pluck('name')->join(', ') ?: '-' }}
                </div>
                <div class="overview-item title">
                    {{ $contentItem->title }}
                </div>
                <div class="overview-item status">
                    <span class="{{ $contentItem->isOnline() ? 'active-item' : 'inactive-item' }}"></span>
                </div>
                <div class="overview-item options">
                    <a href="{{ route($routeNames['edit'], ['id' => $contentItem->id]) }}" title="{{ __('Bewerken') }}" wire:navigate>
                        <span class="flaticon-create-new-pencil-button"></span>
                    </a>
                    <form method="post" action="{{ route($routeNames['duplicate']) }}">
                        @csrf
                        <input type="hidden" name="itemId" value="{{ $contentItem->id }}">
                        <button type="submit" title="{{ __('Dupliceren') }}">
                            <span class="flaticon-add-to-queue-button"></span>
                        </button>
                    </form>
                    <form method="post" action="{{ route($routeNames['destroy'], $contentItem) }}">
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
                <div class="overview-item title">{{ __('Geen content gevonden.') }}</div>
            </div>
        @endforelse
    </div>

    @if ($items->hasPages())
        @php
            $currentPage = $items->currentPage();
            $lastPage = $items->lastPage();
            $startPage = max(1, $currentPage - 1);
            $endPage = min($lastPage, $currentPage + 1);

            if ($currentPage === 1) {
                $endPage = min($lastPage, 3);
            }

            if ($currentPage === $lastPage) {
                $startPage = max(1, $lastPage - 2);
            }
        @endphp

        <nav class="admin-pagination listing-pagination" aria-label="{{ __('Pagination') }}">
            <ul class="admin-pagination-list">
                @for ($page = $startPage; $page <= $endPage; $page++)
                    <li>
                        @if ($page === $currentPage)
                            <span class="admin-pagination-current" aria-current="page">{{ $page }}</span>
                        @else
                            <button class="admin-pagination-link" type="button" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                        @endif
                    </li>
                @endfor

                @if ($endPage < $lastPage)
                    @if ($endPage < $lastPage - 1)
                        <li><span class="admin-pagination-gap">&hellip;</span></li>
                    @endif
                    <li><button class="admin-pagination-link" type="button" wire:click="gotoPage({{ $lastPage }})">{{ $lastPage }}</button></li>
                @endif

                @if ($items->hasMorePages())
                    <li><button class="admin-pagination-link" type="button" wire:click="nextPage" rel="next" aria-label="{{ __('Next') }}">&rsaquo;</button></li>
                    <li><button class="admin-pagination-link" type="button" wire:click="gotoPage({{ $lastPage }})" aria-label="{{ __('Last page') }}">&rarr;</button></li>
                @else
                    <li><span class="admin-pagination-disabled" aria-hidden="true">&rsaquo;</span></li>
                    <li><span class="admin-pagination-disabled" aria-hidden="true">&rarr;</span></li>
                @endif
            </ul>
        </nav>
    @endif

    @if ($categorySelectorOpen)
        <div class="listing-modal-backdrop" wire:click.self="closeCategorySelector">
            <section class="listing-category-modal" aria-labelledby="listing-category-modal-title" role="dialog" aria-modal="true">
                <header class="listing-category-modal-header">
                    <h2 id="listing-category-modal-title">{{ __('Categorie selecteren') }}</h2>
                    <button type="button" wire:click="closeCategorySelector" title="{{ __('Sluiten') }}">
                        <span class="flaticon-close-button"></span>
                    </button>
                </header>

                <div class="listing-category-modal-body">
                    <div class="listing-category-modal-options">
                        <label class="listing-filter-check">
                            <input type="checkbox" wire:model.live="draftShowChild">
                            <span>{{ __('Ook onderliggende') }}</span>
                        </label>
                    </div>

                    <button class="listing-category-option {{ $draftCategoryId === 0 ? 'is-selected' : '' }}" type="button" wire:click="clearCategory">
                        <span class="flaticon-rounded-info-button" aria-hidden="true"></span>
                        <span>{{ __('Alle categorieen') }}</span>
                    </button>

                    @include('livewire.admin.partials.category-tree-selector', [
                        'categoriesByParent' => $categoriesByParent,
                        'parentId' => 0,
                        'draftCategoryId' => $draftCategoryId,
                    ])
                </div>
            </section>
        </div>
    @endif
</div>
