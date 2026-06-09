<div class="listing-overview" wire:loading.class="is-loading">
    <div class="overview-container domains-overview-container listing-overview-container">
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
            <div class="overview-item host">
                {{ __('Host') }}
                <button type="button" wire:click="sortBy('host', 'desc')" title="{{ __('Sorteer aflopend') }}">
                    <x-admin.material-icon name="keyboard_arrow_up" />
                </button>
                <button type="button" wire:click="sortBy('host', 'asc')" title="{{ __('Sorteer oplopend') }}">
                    <x-admin.material-icon name="keyboard_arrow_down" />
                </button>
            </div>
            <div class="overview-item name">
                {{ __('Website title') }}
                <button type="button" wire:click="sortBy('name', 'desc')" title="{{ __('Sorteer aflopend') }}">
                    <x-admin.material-icon name="keyboard_arrow_up" />
                </button>
                <button type="button" wire:click="sortBy('name', 'asc')" title="{{ __('Sorteer oplopend') }}">
                    <x-admin.material-icon name="keyboard_arrow_down" />
                </button>
            </div>
            <div class="overview-item template">{{ __('Template') }}</div>
            <div class="overview-item language">{{ __('Locale') }}</div>
            <div class="overview-item status">{{ __('Status') }}</div>
            <div class="overview-item options">{{ __('Options') }}</div>
        </div>

        <form class="overview-row filters" wire:submit.prevent="applyFilters">
            <div class="overview-item id">
                <input name="id" type="text" wire:model="draftId">
                <x-admin.material-icon name="search" class="search-icon" />
            </div>
            <div class="overview-item host">
                <input name="host" type="text" wire:model="draftHost">
                <x-admin.material-icon name="search" class="search-icon" />
            </div>
            <div class="overview-item name">
                <input name="name" type="text" wire:model="draftName">
                <x-admin.material-icon name="search" class="search-icon" />
            </div>
            <div class="overview-item template">
                <select name="template" wire:model="draftTemplateId" aria-label="{{ __('Template') }}">
                    <option value="">{{ __('Select') }}</option>
                    @foreach ($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="overview-item language">
                <select name="locale" wire:model="draftLocale" aria-label="{{ __('Locale') }}">
                    <option value="">{{ __('Select') }}</option>
                    @foreach ($localeOptions as $locale)
                        <option value="{{ $locale }}">{{ $this->localeLabel($locale) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="overview-item status">
                <select name="status" wire:model="draftStatus">
                    <option value="">{{ __('Select') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="inactive">{{ __('Inactive') }}</option>
                </select>
            </div>
            <div class="overview-item options">
                <button type="submit" title="{{ __('Search') }}" wire:loading.attr="disabled">
                    <x-admin.material-icon name="search" />
                </button>
                <button type="button" title="{{ __('Reset') }}" wire:click="resetFilters" wire:loading.attr="disabled">
                    <x-admin.material-icon name="close" />
                </button>
            </div>
        </form>

        @forelse ($domains as $domain)
            <div class="overview-row" wire:key="domain-overview-row-{{ $domain->id }}">
                <div class="overview-item id">{{ $domain->id }}</div>
                <div class="overview-item host">
                    <a href="{{ route('admin.domains.edit', $domain) }}">{{ $domain->host }}</a>
                </div>
                <div class="overview-item name">{{ $domain->name }}</div>
                <div class="overview-item template">{{ $domain->template?->name ?? __('None') }}</div>
                <div class="overview-item language">
                    <x-admin.language-flag :locale="$domain->default_locale ?: config('app.locale')" />
                </div>
                <div class="overview-item status">
                    <x-admin.quick-status model="domain" :record="$domain" :value="$domain->is_active" :active="$domain->is_active" />
                </div>
                <div class="overview-item options">
                    <a href="{{ route('admin.domains.edit', $domain) }}" title="{{ __('Edit') }}">
                        <x-admin.material-icon name="edit" />
                    </a>
                    <form method="post" action="{{ route('admin.domains.destroy', $domain) }}" data-delete-item-name="{{ $domain->host }}" data-delete-item-id="{{ $domain->id }}">
                        @csrf
                        @method('delete')
                        <button type="submit" title="{{ __('Delete') }}">
                            <x-admin.material-icon name="delete" />
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="overview-row">
                <div class="overview-item host">{{ __('No domains found.') }}</div>
            </div>
        @endforelse
    </div>

    @if ($domains->hasPages())
        @php
            $currentPage = $domains->currentPage();
            $lastPage = $domains->lastPage();
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

                @if ($domains->hasMorePages())
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
