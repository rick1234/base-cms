<div class="catalog-option-editor">
    @if ($message)
        <div class="content-album-message catalog-option-editor-message is-{{ $messageLevel }}" data-flash-message>
            <span>{{ $message }}</span>
            <button type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    <form id="catalog-options-form" wire:submit.prevent="save">
        <div class="response-mail-builder-toolbar catalog-option-editor-toolbar">
            <div class="response-mail-builder-title">
                <h2 class="title">{{ __('Product opties') }}</h2>
                <span>{{ trans_choice('{0} Geen labels|{1} :count label|[2,*] :count labels', count($groups), ['count' => count($groups)]) }}</span>
            </div>
            <div class="response-mail-builder-actions">
                <button class="btn btn-save" type="submit" wire:loading.attr="disabled" wire:target="save">
                    <x-admin.material-icon name="save" />
                    <span wire:loading.remove wire:target="save">{{ __('Opties opslaan') }}</span>
                    <span wire:loading wire:target="save">{{ __('Opslaan...') }}</span>
                </button>
                <button class="btn btn-add" type="button" wire:click="addGroup">
                    <x-admin.material-icon name="add" />
                    {{ __('Label toevoegen') }}
                </button>
            </div>
        </div>

        <div class="catalog-option-editor-grid">
            <aside class="response-mail-tag-panel catalog-option-help" aria-labelledby="catalog-option-help-title">
                <h2 id="catalog-option-help-title" class="title">{{ __('Structuur') }}</h2>
                <div class="response-mail-tag-group">
                    <h3>{{ __('Labels') }}</h3>
                    <p>{{ __('Maak labels zoals kleur, breedte of hoogte. Vul vertalingen per taal in.') }}</p>
                </div>
                <div class="response-mail-tag-group">
                    <h3>{{ __('Opties') }}</h3>
                    <p>{{ __('Voeg onder elk label de mogelijke waarden toe, zoals rood, blauw, 120 cm of XL.') }}</p>
                </div>
            </aside>

            <div class="response-mail-list catalog-option-group-list">
                @foreach ($groups as $groupIndex => $group)
                    <article class="response-mail-card catalog-option-group-card" wire:key="catalog-option-group-{{ $group['key'] }}">
                        <header class="response-mail-card-header">
                            <div>
                                <span class="response-mail-card-kicker">{{ __('Label') }} {{ $groupIndex + 1 }}</span>
                                <h3>{{ $group['label'] ?: __('Nieuw optielabel') }}</h3>
                            </div>
                            <div class="response-mail-card-actions">
                                <button class="btn btn-icon-only" type="button" wire:click="moveGroupUp({{ $groupIndex }})" @disabled($groupIndex === 0) title="{{ __('Omhoog verplaatsen') }}">
                                    <x-admin.material-icon name="keyboard_arrow_up" />
                                </button>
                                <button class="btn btn-icon-only" type="button" wire:click="moveGroupDown({{ $groupIndex }})" @disabled($loop->last) title="{{ __('Omlaag verplaatsen') }}">
                                    <x-admin.material-icon name="keyboard_arrow_down" />
                                </button>
                                <button class="btn btn-duplicate btn-icon-only" type="button" wire:click="duplicateGroup({{ $groupIndex }})" title="{{ __('Dupliceren') }}">
                                    <x-admin.material-icon name="content_copy" />
                                </button>
                                <button class="btn btn-remove btn-icon-only" type="button" wire:click="removeGroup({{ $groupIndex }})" title="{{ __('Verwijderen') }}">
                                    <x-admin.material-icon name="delete" />
                                </button>
                            </div>
                        </header>

                        <input type="hidden" wire:model="groups.{{ $groupIndex }}.id">

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="catalog-option-label-{{ $group['key'] }}">{{ __('Interne labelnaam') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="catalog-option-label-{{ $group['key'] }}" type="text" wire:model.live.debounce.400ms="groups.{{ $groupIndex }}.label">
                                @error("groups.{$groupIndex}.label")
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="catalog-option-locale-grid">
                            @foreach ($locales as $locale)
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="catalog-option-label-{{ $group['key'] }}-{{ $locale }}">{{ __('Label :locale', ['locale' => strtoupper($locale)]) }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="catalog-option-label-{{ $group['key'] }}-{{ $locale }}" type="text" wire:model.live.debounce.400ms="groups.{{ $groupIndex }}.label_translations.{{ $locale }}">
                                        @error("groups.{$groupIndex}.label_translations.{$locale}")
                                            <span class="error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <section class="catalog-option-values" aria-label="{{ __('Opties onder label') }}">
                            <div class="catalog-option-values-header">
                                <div>
                                    <h4>{{ __('Opties') }}</h4>
                                    <span>{{ trans_choice('{0} Geen opties|{1} :count optie|[2,*] :count opties', count($group['values'] ?? []), ['count' => count($group['values'] ?? [])]) }}</span>
                                </div>
                                <button class="btn btn-add" type="button" wire:click="addValue({{ $groupIndex }})">
                                    <x-admin.material-icon name="add" />
                                    {{ __('Optie toevoegen') }}
                                </button>
                            </div>

                            <div class="catalog-option-value-list">
                                @foreach (($group['values'] ?? []) as $valueIndex => $value)
                                    <article class="catalog-option-value-card" wire:key="catalog-option-value-{{ $value['key'] }}">
                                        <header class="catalog-option-value-header">
                                            <span>{{ __('Optie') }} {{ $valueIndex + 1 }}</span>
                                            <div class="response-mail-card-actions">
                                                <button class="btn btn-icon-only" type="button" wire:click="moveValueUp({{ $groupIndex }}, {{ $valueIndex }})" @disabled($valueIndex === 0) title="{{ __('Omhoog verplaatsen') }}">
                                                    <x-admin.material-icon name="keyboard_arrow_up" />
                                                </button>
                                                <button class="btn btn-icon-only" type="button" wire:click="moveValueDown({{ $groupIndex }}, {{ $valueIndex }})" @disabled($loop->last) title="{{ __('Omlaag verplaatsen') }}">
                                                    <x-admin.material-icon name="keyboard_arrow_down" />
                                                </button>
                                                <button class="btn btn-remove btn-icon-only" type="button" wire:click="removeValue({{ $groupIndex }}, {{ $valueIndex }})" title="{{ __('Verwijderen') }}">
                                                    <x-admin.material-icon name="delete" />
                                                </button>
                                            </div>
                                        </header>

                                        <input type="hidden" wire:model="groups.{{ $groupIndex }}.values.{{ $valueIndex }}.id">

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="catalog-option-value-{{ $value['key'] }}">{{ __('Interne optienaam') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input id="catalog-option-value-{{ $value['key'] }}" type="text" wire:model.live.debounce.400ms="groups.{{ $groupIndex }}.values.{{ $valueIndex }}.value">
                                                @error("groups.{$groupIndex}.values.{$valueIndex}.value")
                                                    <span class="error">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="catalog-option-locale-grid">
                                            @foreach ($locales as $locale)
                                                <div class="form-item">
                                                    <div class="form-item-label">
                                                        <label for="catalog-option-value-{{ $value['key'] }}-{{ $locale }}">{{ __('Waarde :locale', ['locale' => strtoupper($locale)]) }}</label>
                                                    </div>
                                                    <div class="form-item-input">
                                                        <input id="catalog-option-value-{{ $value['key'] }}-{{ $locale }}" type="text" wire:model.live.debounce.400ms="groups.{{ $groupIndex }}.values.{{ $valueIndex }}.value_translations.{{ $locale }}">
                                                        @error("groups.{$groupIndex}.values.{$valueIndex}.value_translations.{$locale}")
                                                            <span class="error">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    </article>
                @endforeach
            </div>
        </div>
    </form>
</div>
