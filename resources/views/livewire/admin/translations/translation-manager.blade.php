<div class="main has-buttons">
    <div class="buttons-container align-right">
        <button class="btn" type="button" wire:click="syncTranslations" wire:loading.attr="disabled" wire:target="syncTranslations">
            <x-admin.material-icon name="refresh" />
            <span wire:loading.remove wire:target="syncTranslations">{{ __('Synchroniseren') }}</span>
            <span wire:loading wire:target="syncTranslations">{{ __('Synchroniseren...') }}</span>
        </button>
        <button class="btn btn-add" type="button" wire:click="newKey">
            <x-admin.material-icon name="add" />
            {{ __('Toevoegen') }}
        </button>
    </div>

    @if ($message)
        <div class="content-album-message translation-live-message is-{{ $messageLevel }}" data-flash-message>
            <span>{{ $message }}</span>
            <button type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    @if ($editorOpen)
        <section class="main-section translation-editor-section" aria-labelledby="translation-editor-title">
            <div class="translation-editor-heading">
                <div>
                    @include('admin.translations.partials.page-header', [
                        'title' => __($editorTitle),
                        'section' => __($editorTitle),
                        'usesCmsRoutes' => $usesCmsRoutes,
                    ])
                    <span class="content-admin-screen-label">{{ __($editorTitle) }}</span>
                </div>
                <button class="btn btn-cancel" type="button" wire:click="closeEditor">
                    <x-admin.material-icon name="close" />
                    {{ __('Sluiten') }}
                </button>
            </div>

            <form id="translation-live-form" class="translation-live-form" wire:submit.prevent="saveKey">
                <div class="grid">
                    <div class="grid-row">
                        <div class="col-6">
                            <h2 class="title" id="translation-editor-title">{{ __('Definition') }}</h2>

                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation-area">{{ __('Area') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <select id="translation-area" wire:model="form.area">
                                        @foreach ($areas as $areaKey => $label)
                                            <option value="{{ $areaKey }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.area')
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation-group">{{ __('Group') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="translation-group" type="text" wire:model="form.group" required>
                                    @error('form.group')
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation-key">{{ __('Key') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="translation-key" type="text" wire:model="form.key" required>
                                    @error('form.key')
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation-source-locale">{{ __('Source language') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <select id="translation-source-locale" wire:model.live="form.source_locale">
                                        @foreach ($languages as $language)
                                            <option value="{{ $language['code'] }}">{{ $language['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.source_locale')
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation-source-text">{{ __('Source text') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <textarea id="translation-source-text" wire:model.live.debounce.900ms="form.source_text"></textarea>
                                    @error('form.source_text')
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation-description">{{ __('Omschrijving') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <textarea id="translation-description" wire:model.live.debounce.900ms="form.description"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <h2 class="title">{{ __('Translations') }}</h2>

                            <div class="translation-value-list">
                                @foreach ($languages as $language)
                                    <div class="translation-value-panel" wire:key="translation-editor-value-{{ $language['code'] }}">
                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label class="language-panel-title" for="translation-value-{{ $language['code'] }}">
                                                    <x-admin.language-flag :locale="$language['code']" :label="$language['label']" decorative />
                                                    {{ $language['label'] }}
                                                </label>
                                            </div>
                                            <div class="form-item-input">
                                                <textarea id="translation-value-{{ $language['code'] }}" wire:model.live.debounce.900ms="form.values.{{ $language['code'] }}.value"></textarea>
                                                @error("form.values.{$language['code']}.value")
                                                    <span class="error">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <label class="translation-review-option">
                                            <input type="checkbox" wire:model.live="form.values.{{ $language['code'] }}.is_reviewed">
                                            <span class="checkbox"></span>
                                            {{ __('Reviewed') }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <h2 class="title">{{ __('Settings') }}</h2>

                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="translation-status">{{ __('Status') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <select id="translation-status" wire:model.live="form.status">
                                        <option value="active">{{ __('Actief') }}</option>
                                        <option value="inactive">{{ __('Inactief') }}</option>
                                    </select>
                                </div>
                            </div>

                            <label class="translation-review-option">
                                <input type="checkbox" wire:model.live="form.is_system">
                                <span class="checkbox"></span>
                                {{ __('System managed') }}
                            </label>

                            <div class="translation-live-actions">
                                <button class="btn btn-save" type="submit" wire:loading.attr="disabled" wire:target="saveKey">
                                    <x-admin.material-icon name="save" />
                                    {{ __('Opslaan') }}
                                </button>
                                @if ($editingId)
                                    <button class="btn btn-remove" type="button" wire:click="deleteKey({{ $editingId }})" wire:confirm="{{ __('Deze vertaling verwijderen') }}">
                                        <x-admin.material-icon name="delete" />
                                        {{ __('Verwijderen') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    @endif

    <div class="main-section">
        @include('admin.translations.partials.page-header', [
            'title' => __('Translations'),
            'section' => __('Translation overview'),
            'usesCmsRoutes' => $usesCmsRoutes,
        ])

        <span class="content-admin-screen-label">{{ __('Translation overview') }}</span>

        <div class="translation-filter-bar">
            <label>
                <span>{{ __('Area') }}</span>
                <select wire:model.live="area">
                    <option value="">{{ __('Alle') }}</option>
                    @foreach ($areas as $areaKey => $label)
                        <option value="{{ $areaKey }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>{{ __('Group') }}</span>
                <select wire:model.live="group">
                    <option value="">{{ __('Alle') }}</option>
                    @foreach ($groups as $groupOption)
                        <option value="{{ $groupOption }}">{{ $groupOption }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>{{ __('Language') }}</span>
                <select wire:model.live="locale">
                    @foreach ($languages as $language)
                        <option value="{{ $language['code'] }}">{{ $language['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>{{ __('Status') }}</span>
                <select wire:model.live="status">
                    <option value="">{{ __('Alle') }}</option>
                    <option value="active">{{ __('Actief') }}</option>
                    <option value="inactive">{{ __('Inactief') }}</option>
                </select>
            </label>
            <label>
                <span>{{ __('Search') }}</span>
                <input type="text" wire:model.live.debounce.500ms="search">
            </label>
            <label class="translation-filter-check">
                <input type="checkbox" wire:model.live="missing">
                <span class="checkbox"></span>
                {{ __('Only missing') }}
            </label>
        </div>

        <div class="overview-container translations-overview-container translation-live-overview">
            <div class="overview-row header">
                <div class="overview-item area">{{ __('Area') }}</div>
                <div class="overview-item key">{{ __('Key') }}</div>
                <div class="overview-item source">{{ __('Source') }}</div>
                <div class="overview-item value">
                    <span class="translation-language-heading">
                        <x-admin.language-flag :locale="$locale" :label="$selectedLanguage" decorative />
                        <span>{{ $selectedLanguage }}</span>
                    </span>
                </div>
                <div class="overview-item status">{{ __('Status') }}</div>
                <div class="overview-item options">{{ __('Opties') }}</div>
            </div>

            @forelse ($translationKeys as $translationKey)
                <div class="overview-row" wire:key="translation-row-{{ $translationKey->id }}">
                    <div class="overview-item area">{{ $areas[$translationKey->area] ?? $translationKey->area }}</div>
                    <div class="overview-item key">
                        <button class="button-link translation-key-button" type="button" wire:click="editKey({{ $translationKey->id }})">
                            {{ $translationKey->label() }}
                        </button>
                    </div>
                    <div class="overview-item source">{{ $translationKey->source_text ?: $translationKey->key }}</div>
                    <div class="overview-item value">
                        <textarea wire:model.live.debounce.900ms="values.{{ $translationKey->id }}"></textarea>
                    </div>
                    <div class="overview-item status">
                        <span wire:loading.remove wire:target="values.{{ $translationKey->id }}" class="{{ filled($values[$translationKey->id] ?? null) ? 'active-item' : 'inactive-item' }}"></span>
                        <span wire:loading wire:target="values.{{ $translationKey->id }}" class="translation-saving-indicator"></span>
                        @if (($valueStates[$translationKey->id] ?? null) === 'saved')
                            <span class="translation-saved-label">{{ __('Saved') }}</span>
                        @endif
                    </div>
                    <div class="overview-item options">
                        <button class="button-link" type="button" wire:click="editKey({{ $translationKey->id }})" title="{{ __('Bewerken') }}">
                            <x-admin.material-icon name="edit" />
                        </button>
                        <button class="button-link" type="button" wire:click="deleteKey({{ $translationKey->id }})" wire:confirm="{{ __('Deze vertaling verwijderen') }}" title="{{ __('Verwijderen') }}">
                            <x-admin.material-icon name="delete" />
                        </button>
                    </div>
                </div>
            @empty
                <div class="overview-row">
                    <div class="overview-item key">{{ __('No translations found.') }}</div>
                </div>
            @endforelse
        </div>

        {{ $translationKeys->links('admin.partials.pagination') }}
    </div>
</div>
