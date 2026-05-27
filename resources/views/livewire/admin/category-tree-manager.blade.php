<div class="category-tree-manager" wire:loading.class="is-loading">
    @if ($message)
        <div class="content-album-message category-tree-message" data-flash-message>
            <span>{{ $message }}</span>
            <button type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <span class="flaticon-cancel-button"></span>
            </button>
        </div>
    @endif

    <div class="category-tree-toolbar">
        <a class="btn btn-add" href="{{ $rootCreateUrl }}">
            <span class="flaticon-add-plus-button"></span>
            {{ __('Hoofdcategorie toevoegen') }}
        </a>
        <a class="btn btn-cancel" href="{{ $moduleItemsUrl }}">
            <span class="flaticon-undo-button"></span>
            {{ __('Terug naar overzicht') }}
        </a>
    </div>

    <div class="category-tree-browser">
        <section class="category-tree-panel" aria-labelledby="category-tree-title">
            <h2 id="category-tree-title" class="sub-title">{{ __('Categorieen') }}</h2>

            @if ($categories->isNotEmpty())
                <div class="categories-tree">
                    @include('livewire.admin.partials.category-tree-manager-node', [
                        'categoriesByParent' => $categoriesByParent,
                        'parentId' => 0,
                        'selectedCategoryId' => $selectedCategory?->id,
                    ])
                </div>
            @else
                <div class="attachment-message">
                    <span class="flaticon-rounded-info-button"></span>
                    <em>{{ __('Er zijn nog geen categorieen toegevoegd.') }}</em>
                </div>
            @endif
        </section>

        <section class="category-detail-panel" aria-labelledby="category-detail-title">
            @if ($selectedCategory)
                <div class="category-detail-heading">
                    <div>
                        <h2 id="category-detail-title" class="title">{{ $selectedCategory->name }}</h2>
                        <span class="category-detail-status">
                            <span class="{{ $this->statusClass($selectedCategory) }}"></span>
                            {{ $this->statusLabel($selectedCategory) }}
                        </span>
                    </div>
                    <button class="category-detail-close" type="button" wire:click="clearSelection" title="{{ __('Sluiten') }}">
                        <span class="flaticon-close-button"></span>
                    </button>
                </div>

                <div class="content-category-actions">
                    <a class="btn btn-add" href="{{ $this->categoryCreateUrl($selectedCategory->id) }}">
                        <span class="flaticon-add-plus-button"></span>
                        {{ __('Subcategorie toevoegen') }}
                    </a>
                    <a class="btn" href="{{ $this->categoryEditUrl($selectedCategory) }}">
                        <span class="flaticon-create-new-pencil-button"></span>
                        {{ __('Bewerken') }}
                    </a>
                    <form method="post" action="{{ $this->categoryDeleteUrl($selectedCategory) }}">
                        @csrf
                        @method('delete')
                        <button class="btn btn-remove" type="submit">
                            <span class="flaticon-close-button"></span>
                            {{ __('Verwijderen') }}
                        </button>
                    </form>
                </div>

                <dl class="cms-module-details category-detail-list">
                    <dt>{{ __('URL') }}</dt>
                    <dd>
                        @if ($selectedUrl)
                            <a href="{{ $selectedUrl }}" target="_blank" rel="noopener">{{ $selectedUrl }}</a>
                        @else
                            -
                        @endif
                    </dd>
                    <dt>{{ __('Slug') }}</dt>
                    <dd>{{ $selectedCategory->slug ?: '-' }}</dd>
                    <dt>{{ __('Totaal gekoppeld') }}</dt>
                    <dd>{{ $linkedCount }}</dd>
                </dl>

                <div class="category-linked-items">
                    <div class="category-linked-heading">
                        <h3 class="sub-title">{{ $linkedLabel }}</h3>
                        <a class="btn" href="{{ $this->moduleItemsUrl($selectedCategory) }}">
                            <span class="flaticon-visibility-button"></span>
                            {{ __('Bekijk gekoppelde items') }}
                        </a>
                    </div>

                    @if ($linkedItems->isNotEmpty())
                        <ol class="category-linked-list">
                            @foreach ($linkedItems as $linkedItem)
                                <li wire:key="category-linked-item-{{ $linkedItem->getKey() }}">{{ $this->linkedItemTitle($linkedItem) }}</li>
                            @endforeach
                        </ol>

                        @if ($linkedCount > $linkedItems->count())
                            <p class="category-linked-more">
                                {{ __('Nog :count extra gekoppelde items.', ['count' => $linkedCount - $linkedItems->count()]) }}
                            </p>
                        @endif
                    @else
                        <div class="attachment-message">
                            <span class="flaticon-rounded-info-button"></span>
                            <em>{{ __('Geen gekoppelde items gevonden.') }}</em>
                        </div>
                    @endif
                </div>
            @else
                <div class="attachment-message">
                    <span class="flaticon-rounded-info-button"></span>
                    <em>{{ __('Selecteer een categorie om de URL en gekoppelde items te bekijken.') }}</em>
                </div>
            @endif
        </section>
    </div>
</div>
