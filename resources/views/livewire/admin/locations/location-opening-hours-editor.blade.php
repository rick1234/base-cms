<div class="location-hours-editor">
    @if ($message)
        <div class="content-album-message location-hours-message is-{{ $messageLevel }}" data-flash-message role="alert">
            <span>{{ $message }}</span>
            <button type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    <form id="location-opening-hours-editor-form" wire:submit.prevent="save">
        <div class="location-hours-toolbar">
            <div>
                <h2 class="title">{{ __('Openingstijden') }}</h2>
                <span>{{ __('Manage weekly hours and exceptions for this location.') }}</span>
            </div>
            <div class="location-hours-toolbar-actions">
                <button class="btn btn-save" type="submit" wire:loading.attr="disabled" wire:target="save">
                    <x-admin.material-icon name="save" />
                    <span wire:loading.remove wire:target="save">{{ __('Opslaan') }}</span>
                    <span wire:loading wire:target="save">{{ __('Opslaan...') }}</span>
                </button>
            </div>
        </div>

        <section class="location-hours-panel" aria-labelledby="location-weekly-hours-title">
            <header class="location-hours-panel-header">
                <div>
                    <h3 id="location-weekly-hours-title" class="sub-title">{{ __('Weekly schedule') }}</h3>
                    <span>{{ __('Set the regular opening hours per day.') }}</span>
                </div>
            </header>

            <div class="location-hours-week-grid">
                @foreach ($dayNames as $day => $label)
                    @php $isClosed = (bool) ($openingHours[$day]['is_closed'] ?? false); @endphp
                    <article class="location-hours-day-card {{ $isClosed ? 'is-closed' : '' }}" wire:key="location-hours-day-{{ $day }}">
                        <header class="location-hours-day-header">
                            <strong>{{ $label }}</strong>
                            <button
                                class="location-hours-state-toggle"
                                type="button"
                                wire:click="toggleDayClosed('{{ $day }}')"
                                aria-pressed="{{ $isClosed ? 'true' : 'false' }}"
                            >
                                <span>{{ $isClosed ? __('Gesloten') : __('Open') }}</span>
                            </button>
                        </header>

                        <div class="location-hours-time-grid">
                            <label>
                                <span>{{ __('Open') }}</span>
                                <input type="time" wire:model.live="openingHours.{{ $day }}.opens_at" @disabled($isClosed)>
                            </label>
                            <label>
                                <span>{{ __('Gesloten') }}</span>
                                <input type="time" wire:model.live="openingHours.{{ $day }}.closes_at" @disabled($isClosed)>
                            </label>
                        </div>

                        @error("openingHours.{$day}.opens_at")
                            <p class="location-hours-error">{{ $message }}</p>
                        @enderror
                        @error("openingHours.{$day}.closes_at")
                            <p class="location-hours-error">{{ $message }}</p>
                        @enderror
                    </article>
                @endforeach
            </div>
        </section>

        <section class="location-hours-panel" aria-labelledby="location-special-hours-title">
            <header class="location-hours-panel-header">
                <div>
                    <h3 id="location-special-hours-title" class="sub-title">{{ __('Speciale openingstijden') }}</h3>
                    <span>{{ trans_choice('{0} Geen uitzonderingen|{1} :count uitzondering|[2,*] :count uitzonderingen', count(array_filter($specialOpeningHours, fn (array $row): bool => ! ($row['delete'] ?? false))), ['count' => count(array_filter($specialOpeningHours, fn (array $row): bool => ! ($row['delete'] ?? false)))]) }}</span>
                </div>
                <button class="btn btn-add" type="button" wire:click="addSpecialOpeningHour">
                    <x-admin.material-icon name="add" />
                    {{ __('Uitzondering toevoegen') }}
                </button>
            </header>

            <div class="location-special-hours-list">
                @forelse ($specialOpeningHours as $index => $specialOpeningHour)
                    <article class="location-special-hours-card {{ ($specialOpeningHour['delete'] ?? false) ? 'is-marked-for-delete' : '' }}" wire:key="location-special-hour-{{ $specialOpeningHour['state_id'] }}">
                        <div class="location-special-hours-main">
                            <label class="location-special-hours-title">
                                <span>{{ __('Titel') }}</span>
                                <input type="text" wire:model.live.debounce.500ms="specialOpeningHours.{{ $index }}.title" placeholder="{{ __('Titel') }}">
                            </label>
                            <label>
                                <span>{{ __('Datum') }}</span>
                                <input type="date" wire:model.live="specialOpeningHours.{{ $index }}.date">
                            </label>
                            <label>
                                <span>{{ __('Open') }}</span>
                                <input type="time" wire:model.live="specialOpeningHours.{{ $index }}.opens_at" @disabled($specialOpeningHour['is_closed'] ?? false)>
                            </label>
                            <label>
                                <span>{{ __('Gesloten') }}</span>
                                <input type="time" wire:model.live="specialOpeningHours.{{ $index }}.closes_at" @disabled($specialOpeningHour['is_closed'] ?? false)>
                            </label>
                        </div>

                        <div class="location-special-hours-actions">
                            <button
                                class="btn btn-icon-only"
                                type="button"
                                wire:click="toggleSpecialClosed({{ $index }})"
                                title="{{ ($specialOpeningHour['is_closed'] ?? false) ? __('Open') : __('Gesloten') }}"
                                aria-pressed="{{ ($specialOpeningHour['is_closed'] ?? false) ? 'true' : 'false' }}"
                            >
                                <x-admin.material-icon :name="($specialOpeningHour['is_closed'] ?? false) ? 'lock' : 'lock_open'" />
                            </button>

                            @if ($specialOpeningHour['delete'] ?? false)
                                <button class="btn btn-add btn-icon-only" type="button" wire:click="restoreSpecialOpeningHour({{ $index }})" title="{{ __('Herstellen') }}">
                                    <x-admin.material-icon name="undo" />
                                </button>
                            @else
                                <button class="btn btn-remove btn-icon-only" type="button" wire:click="removeSpecialOpeningHour({{ $index }})" title="{{ __('Verwijderen') }}">
                                    <x-admin.material-icon name="delete" />
                                </button>
                            @endif
                        </div>

                        @if ($specialOpeningHour['delete'] ?? false)
                            <p class="location-hours-muted">{{ __('Deze uitzondering wordt verwijderd na opslaan.') }}</p>
                        @endif

                        @error("specialOpeningHours.{$index}.title")
                            <p class="location-hours-error">{{ $message }}</p>
                        @enderror
                        @error("specialOpeningHours.{$index}.date")
                            <p class="location-hours-error">{{ $message }}</p>
                        @enderror
                        @error("specialOpeningHours.{$index}.opens_at")
                            <p class="location-hours-error">{{ $message }}</p>
                        @enderror
                        @error("specialOpeningHours.{$index}.closes_at")
                            <p class="location-hours-error">{{ $message }}</p>
                        @enderror
                    </article>
                @empty
                    <div class="location-hours-empty">
                        <x-admin.material-icon name="event_busy" />
                        <span>{{ __('Geen speciale openingstijden ingesteld.') }}</span>
                    </div>
                @endforelse
            </div>
        </section>
    </form>
</div>
