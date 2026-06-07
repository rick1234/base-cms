<div class="listing-overview template-listing-overview" wire:loading.class="is-loading">
    <div class="overview-container template-overview-container listing-overview-container">
        <div class="overview-row header">
            <div class="overview-item id">
                Id
                <button type="button" wire:click="sortBy('id', 'desc')" title="{{ __('Nieuwste eerst') }}">
                    <x-admin.material-icon name="keyboard_arrow_up" />
                </button>
                <button type="button" wire:click="sortBy('id', 'asc')" title="{{ __('Oudste eerst') }}">
                    <x-admin.material-icon name="keyboard_arrow_down" />
                </button>
            </div>
            <div class="overview-item name">
                {{ __('Name') }}
                <button type="button" wire:click="sortBy('name', 'asc')" title="{{ __('Sorteer oplopend') }}">
                    <x-admin.material-icon name="keyboard_arrow_up" />
                </button>
                <button type="button" wire:click="sortBy('name', 'desc')" title="{{ __('Sorteer aflopend') }}">
                    <x-admin.material-icon name="keyboard_arrow_down" />
                </button>
                <span class="listing-sort-state" aria-live="polite">
                    @if ($sort === 'name')
                        {{ strtoupper($direction) }}
                    @endif
                </span>
            </div>
            <div class="overview-item handle">
                {{ __('Handle') }}
                <button type="button" wire:click="sortBy('handle', 'asc')" title="{{ __('Sorteer oplopend') }}">
                    <x-admin.material-icon name="keyboard_arrow_up" />
                </button>
                <button type="button" wire:click="sortBy('handle', 'desc')" title="{{ __('Sorteer aflopend') }}">
                    <x-admin.material-icon name="keyboard_arrow_down" />
                </button>
            </div>
            <div class="overview-item domains">{{ __('Domains') }}</div>
            <div class="overview-item sections">{{ __('Sections') }}</div>
            <div class="overview-item status">{{ __('Status') }}</div>
            <div class="overview-item options">{{ __('Opties') }}</div>
        </div>

        <form class="overview-row filters" wire:submit.prevent="applyFilters">
            <div class="overview-item id">
                <input name="id" type="text" wire:model="draftId">
                <x-admin.material-icon name="search" class="search-icon" />
            </div>
            <div class="overview-item name">
                <input name="name" type="text" wire:model="draftName">
                <x-admin.material-icon name="search" class="search-icon" />
            </div>
            <div class="overview-item handle">
                <input name="handle" type="text" wire:model="draftHandle">
                <x-admin.material-icon name="search" class="search-icon" />
            </div>
            <div class="overview-item domains"></div>
            <div class="overview-item sections"></div>
            <div class="overview-item status">
                <select name="status" wire:model="draftStatus">
                    <option value="">{{ __('Selecteer') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="inactive">{{ __('Inactive') }}</option>
                </select>
            </div>
            <div class="overview-item options">
                <button type="submit" title="{{ __('Zoeken') }}" wire:loading.attr="disabled">
                    <x-admin.material-icon name="search" />
                </button>
                <button type="button" title="{{ __('Reset') }}" wire:click="resetFilters" wire:loading.attr="disabled">
                    <x-admin.material-icon name="close" />
                </button>
            </div>
        </form>

        @forelse ($templates as $template)
            <div class="overview-row" wire:key="template-listing-row-{{ $template->id }}">
                <div class="overview-item id">{{ $template->id }}</div>
                <div class="overview-item name">
                    <a href="{{ route('admin.templates.edit', $template) }}" wire:navigate>{{ $template->name }}</a>
                </div>
                <div class="overview-item handle">{{ $template->handle }}</div>
                <div class="overview-item domains">{{ $template->domains_count }}</div>
                <div class="overview-item sections">{{ count($template->defined_sections ?? []) }}</div>
                <div class="overview-item status">
                    <x-admin.quick-status model="website-template" :record="$template" :value="$template->is_active" :active="$template->is_active" />
                </div>
                <div class="overview-item options">
                    <a href="{{ route('admin.templates.edit', $template) }}" title="{{ __('Bewerken') }}" wire:navigate>
                        <x-admin.material-icon name="edit" />
                    </a>
                    <form method="post" action="{{ route('admin.templates.destroy', $template) }}" data-delete-item-name="{{ $template->name }}" data-delete-item-id="{{ $template->id }}">
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
                <div class="overview-item name">{{ __('No templates found.') }}</div>
            </div>
        @endforelse
    </div>

    @if ($templates->hasPages())
        @php
            $currentPage = $templates->currentPage();
            $lastPage = $templates->lastPage();
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

                @if ($templates->hasMorePages())
                    <li><button class="admin-pagination-link" type="button" wire:click="nextPage" rel="next" aria-label="{{ __('Next') }}">&rsaquo;</button></li>
                    <li><button class="admin-pagination-link" type="button" wire:click="gotoPage({{ $lastPage }})" aria-label="{{ __('Last page') }}">&rarr;</button></li>
                @else
                    <li><span class="admin-pagination-disabled" aria-hidden="true">&rsaquo;</span></li>
                    <li><span class="admin-pagination-disabled" aria-hidden="true">&rarr;</span></li>
                @endif
            </ul>
        </nav>
    @endif
</div>
