<div class="response-mail-builder template-usp-set-editor" wire:loading.class="is-loading">
    @if ($message)
        <div class="content-album-message response-mail-builder-message" data-flash-message>
            <span>{{ $message }}</span>
            <button type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    <form id="template-usp-set-editor-form" wire:submit.prevent="save">
        <div class="response-mail-builder-toolbar">
            <div class="response-mail-builder-title">
                <h2 id="template-usp-sets-title" class="title">{{ __('USP sets') }}</h2>
                <span>{{ trans_choice('{0} Geen sets|{1} :count set|[2,*] :count sets', count($sets), ['count' => count($sets)]) }}</span>
            </div>
            <div class="response-mail-builder-actions">
                <button class="btn btn-save" type="submit" wire:loading.attr="disabled" wire:target="save">
                    <x-admin.material-icon name="save" />
                    <span wire:loading.remove wire:target="save">{{ __('USP sets opslaan') }}</span>
                    <span wire:loading wire:target="save">{{ __('Opslaan...') }}</span>
                </button>
                <button class="btn btn-add" type="button" wire:click="addSet">
                    <x-admin.material-icon name="add" />
                    {{ __('USP set toevoegen') }}
                </button>
            </div>
        </div>

        <div class="response-mail-builder-grid template-usp-set-builder-grid">
            <aside class="response-mail-tag-panel template-usp-set-help" aria-labelledby="template-usp-set-help-title">
                <h2 id="template-usp-set-help-title" class="title">{{ __('Structuur') }}</h2>
                <div class="response-mail-tag-group">
                    <h3>{{ __('Set') }}</h3>
                    <p>{{ __('Een set groepeert USP items voor een vaste template locatie.') }}</p>
                </div>
                <div class="response-mail-tag-group">
                    <h3>{{ __('Locatie') }}</h3>
                    <div class="response-mail-tag-list">
                        @foreach ($locations as $label)
                            <span class="response-mail-tag">{{ __($label) }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="response-mail-tag-group">
                    <h3>{{ __('Icon') }}</h3>
                    <p>{{ __('Laat het icon veld leeg om automatisch het standaard vinkje te gebruiken.') }}</p>
                </div>
            </aside>

            <div class="response-mail-list template-usp-set-list">
                @foreach ($sets as $setIndex => $set)
                    <article class="response-mail-card template-usp-set-card" wire:key="template-usp-set-{{ $setIndex }}">
                        <header class="response-mail-card-header">
                            <div>
                                <span class="response-mail-card-kicker">{{ __('Set') }} {{ $setIndex + 1 }}</span>
                                <h3>{{ $set['name'] ?: __('USP set') }}</h3>
                            </div>
                            <div class="response-mail-card-actions">
                                <button class="btn btn-icon-only" type="button" wire:click="moveSet({{ $setIndex }}, 'up')" @disabled($loop->first) title="{{ __('Omhoog verplaatsen') }}">
                                    <x-admin.material-icon name="keyboard_arrow_up" />
                                </button>
                                <button class="btn btn-icon-only" type="button" wire:click="moveSet({{ $setIndex }}, 'down')" @disabled($loop->last) title="{{ __('Omlaag verplaatsen') }}">
                                    <x-admin.material-icon name="keyboard_arrow_down" />
                                </button>
                                <button class="btn btn-remove btn-icon-only" type="button" wire:click="removeSet({{ $setIndex }})" title="{{ __('Verwijderen') }}">
                                    <x-admin.material-icon name="delete" />
                                </button>
                            </div>
                        </header>

                        <div class="response-mail-card-grid">
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="usp_set_{{ $setIndex }}_name">{{ __('Naam') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="usp_set_{{ $setIndex }}_name" type="text" wire:model.live.debounce.300ms="sets.{{ $setIndex }}.name" placeholder="{{ __('Header USP bar') }}">
                                    @error('sets.'.$setIndex.'.name')<span class="error">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="usp_set_{{ $setIndex }}_location">{{ __('Template location') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <select id="usp_set_{{ $setIndex }}_location" wire:model.live="sets.{{ $setIndex }}.location">
                                        @foreach ($locations as $value => $label)
                                            <option value="{{ $value }}">{{ __($label) }}</option>
                                        @endforeach
                                    </select>
                                    @error('sets.'.$setIndex.'.location')<span class="error">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <section class="template-usp-items" aria-label="{{ __('USP items') }}">
                            <div class="template-usp-items-header">
                                <h4>{{ __('USP items') }}</h4>
                                <button class="btn btn-add" type="button" wire:click="addItem({{ $setIndex }})">
                                    <x-admin.material-icon name="add" />
                                    {{ __('USP toevoegen') }}
                                </button>
                            </div>

                            <div class="template-usp-item-list">
                                @foreach ($set['items'] as $itemIndex => $item)
                                    <div class="template-usp-item-row" wire:key="template-usp-item-{{ $setIndex }}-{{ $itemIndex }}">
                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="usp_set_{{ $setIndex }}_item_{{ $itemIndex }}_label">{{ __('Tekst') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input id="usp_set_{{ $setIndex }}_item_{{ $itemIndex }}_label" type="text" wire:model.live.debounce.300ms="sets.{{ $setIndex }}.items.{{ $itemIndex }}.label" placeholder="{{ __('Responsive maatwerk') }}">
                                                @error('sets.'.$setIndex.'.items.'.$itemIndex.'.label')<span class="error">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="usp_set_{{ $setIndex }}_item_{{ $itemIndex }}_icon">{{ __('Icon') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input id="usp_set_{{ $setIndex }}_item_{{ $itemIndex }}_icon" type="text" wire:model.live.debounce.300ms="sets.{{ $setIndex }}.items.{{ $itemIndex }}.icon" placeholder="done">
                                                @error('sets.'.$setIndex.'.items.'.$itemIndex.'.icon')<span class="error">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div class="response-mail-card-actions template-usp-item-actions">
                                            <button class="btn btn-icon-only" type="button" wire:click="moveItem({{ $setIndex }}, {{ $itemIndex }}, 'up')" @disabled($loop->first) title="{{ __('Omhoog verplaatsen') }}">
                                                <x-admin.material-icon name="keyboard_arrow_up" />
                                            </button>
                                            <button class="btn btn-icon-only" type="button" wire:click="moveItem({{ $setIndex }}, {{ $itemIndex }}, 'down')" @disabled($loop->last) title="{{ __('Omlaag verplaatsen') }}">
                                                <x-admin.material-icon name="keyboard_arrow_down" />
                                            </button>
                                            <button class="btn btn-remove btn-icon-only" type="button" wire:click="removeItem({{ $setIndex }}, {{ $itemIndex }})" title="{{ __('Verwijderen') }}">
                                                <x-admin.material-icon name="delete" />
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </article>
                @endforeach

                @if ($sets === [])
                    <article class="response-mail-card">
                        <header class="response-mail-card-header">
                            <div>
                                <span class="response-mail-card-kicker">{{ __('USP sets') }}</span>
                                <h3>{{ trans_choice('{0} Geen sets|{1} :count set|[2,*] :count sets', 0, ['count' => 0]) }}</h3>
                            </div>
                            <div class="response-mail-card-actions">
                                <button class="btn btn-add" type="button" wire:click="addSet">
                                    <x-admin.material-icon name="add" />
                                    {{ __('USP set toevoegen') }}
                                </button>
                            </div>
                        </header>
                    </article>
                @endif
            </div>
        </div>
    </form>
</div>
