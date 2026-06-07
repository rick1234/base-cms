<div class="event-schedule-editor" data-event-schedule-editor>
    @if ($message)
        <div class="content-album-message event-schedule-message is-{{ $messageLevel }}" data-flash-message>
            <span>{{ $message }}</span>
            <button type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    <form id="event-schedule-editor-form" wire:submit.prevent="save">
        <div class="event-schedule-toolbar">
            <div class="event-schedule-title">
                <h2 class="title">{{ __('Tijdschema sets') }}</h2>
                <span>{{ trans_choice('{0} Geen sets|{1} :count set|[2,*] :count sets', count($groups), ['count' => count($groups)]) }}</span>
            </div>
            <div class="event-schedule-actions">
                <button class="btn btn-save" type="submit" wire:loading.attr="disabled" wire:target="save">
                    <x-admin.material-icon name="save" />
                    <span wire:loading.remove wire:target="save">{{ __('Tijdschema opslaan') }}</span>
                    <span wire:loading wire:target="save">{{ __('Opslaan...') }}</span>
                </button>
                <button class="btn btn-add" type="button" wire:click="addGroup">
                    <x-admin.material-icon name="add" />
                    {{ __('Set toevoegen') }}
                </button>
            </div>
        </div>

        @error('groups')
            <p class="event-schedule-error">{{ $message }}</p>
        @enderror

        <div class="event-schedule-groups" data-drag-container data-livewire-sort-method="sortGroup">
            @forelse ($groups as $groupIndex => $group)
                <section
                    class="event-schedule-group {{ ($group['is_collapsed'] ?? false) ? 'is-collapsed' : '' }}"
                    data-drag-item
                    data-drag-id="{{ $group['state_id'] }}"
                    draggable="true"
                    wire:key="event-schedule-group-{{ $group['state_id'] }}"
                >
                    <input type="hidden" wire:model="groups.{{ $groupIndex }}.id">
                    <input type="hidden" wire:model="groups.{{ $groupIndex }}.state_id">

                    <header class="event-schedule-group-header">
                        <button class="event-schedule-drag-handle" type="button" title="{{ __('Sleep om te sorteren') }}">
                            <x-admin.material-icon name="drag_indicator" />
                        </button>

                        <div class="event-schedule-group-name">
                            <label for="event-schedule-group-{{ $group['state_id'] }}-name">{{ __('Set naam') }}</label>
                            <input
                                id="event-schedule-group-{{ $group['state_id'] }}-name"
                                type="text"
                                wire:model.live.debounce.500ms="groups.{{ $groupIndex }}.name"
                                required
                            >
                            @error("groups.{$groupIndex}.name")
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="event-schedule-group-meta">
                            {{ trans_choice('{0} Geen items|{1} :count item|[2,*] :count items', count($group['items'] ?? []), ['count' => count($group['items'] ?? [])]) }}
                        </div>

                        <div class="event-schedule-group-actions">
                            <button class="btn btn-icon-only" type="button" wire:click="sortItemsByStartTime({{ $groupIndex }})" title="{{ __('Sort by start time') }}">
                                <x-admin.material-icon name="sort" />
                            </button>
                            <button class="btn btn-icon-only" type="button" wire:click="toggleGroup({{ $groupIndex }})" title="{{ ($group['is_collapsed'] ?? false) ? __('Uitklappen') : __('Inklappen') }}">
                                <x-admin.material-icon :name="($group['is_collapsed'] ?? false) ? 'unfold_more' : 'unfold_less'" />
                            </button>
                            <button class="btn btn-add btn-icon-only" type="button" wire:click="addItem({{ $groupIndex }})" title="{{ __('Item toevoegen') }}">
                                <x-admin.material-icon name="add" />
                            </button>
                            <button class="btn btn-remove btn-icon-only" type="button" wire:click="removeGroup({{ $groupIndex }})" title="{{ __('Set verwijderen') }}">
                                <x-admin.material-icon name="delete" />
                            </button>
                        </div>
                    </header>

                    @unless ($group['is_collapsed'] ?? false)
                        <div class="event-schedule-entry-list" data-drag-container data-livewire-sort-method="sortItem">
                            @forelse (($group['items'] ?? []) as $itemIndex => $item)
                                <article
                                    class="event-schedule-entry"
                                    data-drag-item
                                    data-drag-id="{{ $item['state_id'] }}"
                                    draggable="true"
                                    wire:key="event-schedule-entry-{{ $group['state_id'] }}-{{ $item['state_id'] }}"
                                >
                                    <input type="hidden" wire:model="groups.{{ $groupIndex }}.items.{{ $itemIndex }}.id">
                                    <input type="hidden" wire:model="groups.{{ $groupIndex }}.items.{{ $itemIndex }}.state_id">

                                    <div class="event-schedule-entry-handle">
                                        <button class="event-schedule-drag-handle" type="button" title="{{ __('Sleep om te sorteren') }}">
                                            <x-admin.material-icon name="drag_indicator" />
                                        </button>
                                        <span>{{ $itemIndex + 1 }}</span>
                                    </div>

                                    <div class="event-schedule-entry-fields">
                                        <div class="form-item event-schedule-entry-title">
                                            <div class="form-item-label">
                                                <label for="event-schedule-entry-{{ $item['state_id'] }}-title">{{ __('Titel') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input
                                                    id="event-schedule-entry-{{ $item['state_id'] }}-title"
                                                    wire:key="event-schedule-entry-{{ $group['state_id'] }}-{{ $item['state_id'] }}-{{ $itemIndex }}-title-input"
                                                    type="text"
                                                    wire:model.live.debounce.500ms="groups.{{ $groupIndex }}.items.{{ $itemIndex }}.title"
                                                >
                                                @error("groups.{$groupIndex}.items.{$itemIndex}.title")
                                                    <span class="error">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="event-schedule-entry-{{ $item['state_id'] }}-date">{{ __('Datum') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input
                                                    id="event-schedule-entry-{{ $item['state_id'] }}-date"
                                                    wire:key="event-schedule-entry-{{ $group['state_id'] }}-{{ $item['state_id'] }}-{{ $itemIndex }}-date-input"
                                                    type="date"
                                                    wire:model.blur="groups.{{ $groupIndex }}.items.{{ $itemIndex }}.date"
                                                >
                                                @error("groups.{$groupIndex}.items.{$itemIndex}.date")
                                                    <span class="error">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="event-schedule-entry-{{ $item['state_id'] }}-starts-at">{{ __('Starttijd') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input
                                                    id="event-schedule-entry-{{ $item['state_id'] }}-starts-at"
                                                    wire:key="event-schedule-entry-{{ $group['state_id'] }}-{{ $item['state_id'] }}-{{ $itemIndex }}-starts-at-input"
                                                    type="time"
                                                    wire:model.blur="groups.{{ $groupIndex }}.items.{{ $itemIndex }}.starts_at"
                                                >
                                                @error("groups.{$groupIndex}.items.{$itemIndex}.starts_at")
                                                    <span class="error">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="event-schedule-entry-{{ $item['state_id'] }}-ends-at">{{ __('Eindtijd') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input
                                                    id="event-schedule-entry-{{ $item['state_id'] }}-ends-at"
                                                    wire:key="event-schedule-entry-{{ $group['state_id'] }}-{{ $item['state_id'] }}-{{ $itemIndex }}-ends-at-input"
                                                    type="time"
                                                    wire:model.blur="groups.{{ $groupIndex }}.items.{{ $itemIndex }}.ends_at"
                                                >
                                                @error("groups.{$groupIndex}.items.{$itemIndex}.ends_at")
                                                    <span class="error">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-item event-schedule-entry-content">
                                            <div class="form-item-label">
                                                <label for="event-schedule-entry-{{ $item['state_id'] }}-content">{{ __('Omschrijving') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <textarea
                                                    id="event-schedule-entry-{{ $item['state_id'] }}-content"
                                                    wire:key="event-schedule-entry-{{ $group['state_id'] }}-{{ $item['state_id'] }}-{{ $itemIndex }}-content-input"
                                                    wire:model.live.debounce.500ms="groups.{{ $groupIndex }}.items.{{ $itemIndex }}.content"
                                                ></textarea>
                                                @error("groups.{$groupIndex}.items.{$itemIndex}.content")
                                                    <span class="error">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="event-schedule-entry-actions">
                                        <button class="btn btn-remove btn-icon-only" type="button" wire:click="removeItem({{ $groupIndex }}, {{ $itemIndex }})" title="{{ __('Item verwijderen') }}">
                                            <x-admin.material-icon name="delete" />
                                        </button>
                                    </div>
                                </article>
                            @empty
                                <div class="event-schedule-empty">
                                    <x-admin.material-icon name="info" />
                                    <span>{{ __('Geen items in deze set.') }}</span>
                                </div>
                            @endforelse
                        </div>
                    @endunless
                </section>
            @empty
                <div class="event-schedule-empty">
                    <x-admin.material-icon name="info" />
                    <span>{{ __('Geen tijdschema sets gevonden.') }}</span>
                    <button class="btn btn-add" type="button" wire:click="addGroup">
                        <x-admin.material-icon name="add" />
                        {{ __('Set toevoegen') }}
                    </button>
                </div>
            @endforelse
        </div>
    </form>
</div>
