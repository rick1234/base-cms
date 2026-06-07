<div class="banner-translation-editor" data-banner-translation-editor>
    @if ($message)
        <div class="content-album-message banner-translation-editor-message is-{{ $messageLevel }}" data-flash-message>
            <span>{{ $message }}</span>
            <button type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    <form id="banner-translation-editor-form" wire:submit.prevent="save">
        <div class="banner-translation-editor-toolbar">
            <div class="banner-translation-editor-title">
                <h2 class="title">{{ __('Vertalingen') }}</h2>
                <span>{{ trans_choice('{0} Geen talen|{1} :count taal|[2,*] :count talen', count($translations), ['count' => count($translations)]) }}</span>
            </div>

            <div class="banner-translation-editor-actions">
                <button class="btn btn-save" type="submit" wire:loading.attr="disabled" wire:target="save">
                    <x-admin.material-icon name="save" />
                    <span wire:loading.remove wire:target="save">{{ __('Vertalingen opslaan') }}</span>
                    <span wire:loading wire:target="save">{{ __('Opslaan...') }}</span>
                </button>
            </div>
        </div>

        <div class="banner-translation-editor-grid">
            <aside class="banner-translation-list" aria-labelledby="banner-translation-list-title">
                <h2 id="banner-translation-list-title" class="title">{{ __('Talen') }}</h2>

                <div class="banner-translation-list-items">
                    @foreach ($translations as $locale => $translation)
                        @php
                            $isSelected = $selectedLocale === $locale;
                            $isConfigured = filled($translation['title'] ?? null)
                                || filled($translation['subtitle'] ?? null)
                                || filled($translation['link_url'] ?? null)
                                || filled($translation['button_text'] ?? null)
                                || filled($translation['content'] ?? null)
                                || filled($translation['alt_text'] ?? null)
                                || filled($translation['aria_label'] ?? null);
                        @endphp
                        <button
                            class="banner-translation-list-item{{ $isSelected ? ' is-active' : '' }}"
                            type="button"
                            wire:click="selectLocale('{{ $locale }}')"
                            aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                        >
                            <span class="banner-translation-list-flag">
                                <x-admin.language-flag :locale="$locale" :label="$translation['label'] ?? strtoupper($locale)" decorative />
                            </span>
                            <span class="banner-translation-list-copy">
                                <strong>{{ $translation['label'] ?? strtoupper($locale) }}</strong>
                                <span>{{ $translation['title'] ?: __('Geen titel') }}</span>
                            </span>
                            <span class="banner-translation-state{{ $isConfigured ? ' is-configured' : '' }}">
                                {{ $isConfigured ? __('Ingevuld') : __('Leeg') }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </aside>

            <section class="banner-translation-pane" aria-labelledby="banner-translation-pane-title">
                <header class="banner-translation-pane-header">
                    <div class="translation-language-heading">
                        <x-admin.language-flag :locale="$selectedLocale" :label="$selectedTranslation['label'] ?? strtoupper($selectedLocale)" decorative />
                        <div>
                            <span>{{ __('Taal') }}</span>
                            <h2 id="banner-translation-pane-title">{{ $selectedTranslation['label'] ?? strtoupper($selectedLocale) }}</h2>
                        </div>
                    </div>
                </header>

                <input type="hidden" wire:model="translations.{{ $selectedLocale }}.locale">

                <div class="banner-translation-form-grid">
                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="banner-translation-title-{{ $selectedLocale }}">{{ __('Titel') }}</label>
                        </div>
                        <div class="form-item-input">
                            <input id="banner-translation-title-{{ $selectedLocale }}" type="text" wire:model.live.debounce.500ms="translations.{{ $selectedLocale }}.title">
                            @error("translations.{$selectedLocale}.title")
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="banner-translation-subtitle-{{ $selectedLocale }}">{{ __('Subtitel') }}</label>
                        </div>
                        <div class="form-item-input">
                            <input id="banner-translation-subtitle-{{ $selectedLocale }}" type="text" wire:model.live.debounce.500ms="translations.{{ $selectedLocale }}.subtitle">
                            @error("translations.{$selectedLocale}.subtitle")
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="banner-translation-link-{{ $selectedLocale }}">{{ __('Link') }}</label>
                        </div>
                        <div class="form-item-input">
                            <input id="banner-translation-link-{{ $selectedLocale }}" type="text" wire:model.live.debounce.500ms="translations.{{ $selectedLocale }}.link_url">
                            @error("translations.{$selectedLocale}.link_url")
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="banner-translation-button-{{ $selectedLocale }}">{{ __('Knop titel') }}</label>
                        </div>
                        <div class="form-item-input">
                            <input id="banner-translation-button-{{ $selectedLocale }}" type="text" wire:model.live.debounce.500ms="translations.{{ $selectedLocale }}.button_text">
                            @error("translations.{$selectedLocale}.button_text")
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="banner-translation-link-target-{{ $selectedLocale }}">{{ __('Link doel') }}</label>
                        </div>
                        <div class="form-item-input">
                            <select id="banner-translation-link-target-{{ $selectedLocale }}" wire:model.live="translations.{{ $selectedLocale }}.link_target">
                                <option value="_self">{{ __('Zelfde venster') }}</option>
                                <option value="_blank">{{ __('Nieuw venster') }}</option>
                            </select>
                            @error("translations.{$selectedLocale}.link_target")
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="banner-translation-alt-{{ $selectedLocale }}">{{ __('Alt tekst') }}</label>
                        </div>
                        <div class="form-item-input">
                            <input id="banner-translation-alt-{{ $selectedLocale }}" type="text" wire:model.live.debounce.500ms="translations.{{ $selectedLocale }}.alt_text">
                            @error("translations.{$selectedLocale}.alt_text")
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-item">
                    <div class="form-item-label">
                        <label for="banner-translation-aria-{{ $selectedLocale }}">{{ __('Toegankelijk linklabel') }}</label>
                    </div>
                    <div class="form-item-input">
                        <input id="banner-translation-aria-{{ $selectedLocale }}" type="text" wire:model.live.debounce.500ms="translations.{{ $selectedLocale }}.aria_label">
                        @error("translations.{$selectedLocale}.aria_label")
                            <span class="error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-item">
                    <div class="form-item-label">
                        <label for="banner-translation-content-{{ $selectedLocale }}">{{ __('Tekst') }}</label>
                    </div>
                    <div class="form-item-input">
                        <textarea id="banner-translation-content-{{ $selectedLocale }}" wire:model.live.debounce.500ms="translations.{{ $selectedLocale }}.content"></textarea>
                        @error("translations.{$selectedLocale}.content")
                            <span class="error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </section>
        </div>
    </form>
</div>
