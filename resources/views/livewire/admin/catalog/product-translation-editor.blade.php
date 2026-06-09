<div class="catalog-translation-editor">
    @if ($message)
        <div class="content-album-message catalog-translation-editor-message is-{{ $messageLevel }}" data-flash-message>
            <span>{{ $message }}</span>
            <button type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    <form id="catalog-translations-form" wire:submit.prevent="save">
        <div class="response-mail-builder-toolbar catalog-translation-editor-toolbar">
            <div class="response-mail-builder-title">
                <h2 class="title">{{ __('Vertalingen') }}</h2>
                <span>{{ trans_choice('{0} Geen vertalingen|{1} :count vertaling|[2,*] :count vertalingen', count($translations), ['count' => count($translations)]) }}</span>
            </div>
            <div class="response-mail-builder-actions">
                <button class="btn btn-save" type="submit" wire:loading.attr="disabled" wire:target="save">
                    <x-admin.material-icon name="save" />
                    <span wire:loading.remove wire:target="save">{{ __('Vertalingen opslaan') }}</span>
                    <span wire:loading wire:target="save">{{ __('Opslaan...') }}</span>
                </button>
                <button class="btn btn-add" type="button" wire:click="addTranslation">
                    <x-admin.material-icon name="add" />
                    {{ __('Vertaling toevoegen') }}
                </button>
            </div>
        </div>

        <div class="catalog-translation-editor-grid">
            <aside class="response-mail-tag-panel catalog-translation-help" aria-labelledby="catalog-translation-help-title">
                <h2 id="catalog-translation-help-title" class="title">{{ __('Vertaalvelden') }}</h2>
                <div class="response-mail-tag-group">
                    <h3>{{ __('Taal') }}</h3>
                    <p>{{ __('Elke kaart beheert een taalversie van dit catalogusproduct.') }}</p>
                </div>
                <div class="response-mail-tag-group">
                    <h3>{{ __('Content') }}</h3>
                    <p>{{ __('Gebruik titel, subtitel, tekst en optionele knopvelden voor de frontend of API.') }}</p>
                </div>
            </aside>

            <div class="response-mail-list catalog-translation-list">
                @foreach ($translations as $index => $translation)
                    <article class="response-mail-card catalog-translation-card" wire:key="catalog-translation-card-{{ $translation['key'] }}">
                        <header class="response-mail-card-header">
                            <div>
                                <span class="response-mail-card-kicker">{{ __('Vertaling') }} {{ $index + 1 }}</span>
                                <h3>{{ filled($translation['title'] ?? null) ? $translation['title'] : strtoupper((string) ($translation['locale'] ?? '')) }}</h3>
                            </div>
                            <div class="response-mail-card-actions">
                                <button class="btn btn-icon-only" type="button" wire:click="moveTranslationUp({{ $index }})" @disabled($index === 0) title="{{ __('Omhoog verplaatsen') }}">
                                    <x-admin.material-icon name="keyboard_arrow_up" />
                                </button>
                                <button class="btn btn-icon-only" type="button" wire:click="moveTranslationDown({{ $index }})" @disabled($loop->last) title="{{ __('Omlaag verplaatsen') }}">
                                    <x-admin.material-icon name="keyboard_arrow_down" />
                                </button>
                                <button class="btn btn-duplicate btn-icon-only" type="button" wire:click="duplicateTranslation({{ $index }})" title="{{ __('Dupliceren') }}">
                                    <x-admin.material-icon name="content_copy" />
                                </button>
                                <button class="btn btn-remove btn-icon-only" type="button" wire:click="removeTranslation({{ $index }})" title="{{ __('Verwijderen') }}">
                                    <x-admin.material-icon name="delete" />
                                </button>
                            </div>
                        </header>

                        <input type="hidden" wire:model="translations.{{ $index }}.id">

                        <div class="response-mail-card-grid">
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="catalog-translation-locale-{{ $translation['key'] }}">{{ __('Taal') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <select id="catalog-translation-locale-{{ $translation['key'] }}" wire:model.live="translations.{{ $index }}.locale">
                                        @foreach ($locales as $locale)
                                            <option value="{{ $locale }}">{{ strtoupper($locale) }}</option>
                                        @endforeach
                                    </select>
                                    @error("translations.{$index}.locale")
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="catalog-translation-title-{{ $translation['key'] }}">{{ __('Titel') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="catalog-translation-title-{{ $translation['key'] }}" type="text" wire:model.live.debounce.500ms="translations.{{ $index }}.title">
                                    @error("translations.{$index}.title")
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="catalog-translation-subtitle-{{ $translation['key'] }}">{{ __('Subtitel') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="catalog-translation-subtitle-{{ $translation['key'] }}" type="text" wire:model.live.debounce.500ms="translations.{{ $index }}.subtitle">
                                @error("translations.{$index}.subtitle")
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="response-mail-card-grid">
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="catalog-translation-button-{{ $translation['key'] }}">{{ __('Knoptekst') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="catalog-translation-button-{{ $translation['key'] }}" type="text" wire:model.live.debounce.500ms="translations.{{ $index }}.button_text">
                                    @error("translations.{$index}.button_text")
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="catalog-translation-link-{{ $translation['key'] }}">{{ __('Link URL') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="catalog-translation-link-{{ $translation['key'] }}" type="text" wire:model.live.debounce.500ms="translations.{{ $index }}.link_url">
                                    @error("translations.{$index}.link_url")
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="catalog-translation-content-{{ $translation['key'] }}">{{ __('Tekst') }}</label>
                            </div>
                            <div class="form-item-input">
                                <textarea id="catalog-translation-content-{{ $translation['key'] }}" wire:model.live.debounce.500ms="translations.{{ $index }}.content"></textarea>
                                @error("translations.{$index}.content")
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </form>
</div>
