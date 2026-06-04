<div class="response-mail-builder" data-response-mail-builder>
    @if ($message)
        <div class="content-album-message response-mail-builder-message is-{{ $messageLevel }}" data-flash-message>
            <span>{{ $message }}</span>
            <button type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    <form id="form-response-mail-builder-form" wire:submit.prevent="save">
        <div class="response-mail-builder-toolbar">
            <div class="response-mail-builder-title">
                <h2 class="title">{{ __('Response mails') }}</h2>
                <span>{{ trans_choice('{0} Geen berichten|{1} :count bericht|[2,*] :count berichten', count($messages), ['count' => count($messages)]) }}</span>
            </div>
            <div class="response-mail-builder-actions">
                <button class="btn btn-save" type="submit" wire:loading.attr="disabled" wire:target="save">
                    <x-admin.material-icon name="save" />
                    <span wire:loading.remove wire:target="save">{{ __('Response mail opslaan') }}</span>
                    <span wire:loading wire:target="save">{{ __('Opslaan...') }}</span>
                </button>
                <button class="btn btn-add" type="button" wire:click="addMessage">
                    <x-admin.material-icon name="add" />
                    {{ __('Response mail toevoegen') }}
                </button>
            </div>
        </div>

        <div class="response-mail-builder-grid">
            <aside class="response-mail-tag-panel" aria-labelledby="response-mail-tags-title">
                <h2 id="response-mail-tags-title" class="title">{{ __('Tags') }}</h2>
                @foreach ($placeholderGroups as $group)
                    <section class="response-mail-tag-group" aria-label="{{ $group['label'] }}">
                        <h3>{{ $group['label'] }}</h3>
                        <div class="response-mail-tag-list">
                            @forelse ($group['tags'] as $tag)
                                <button
                                    class="response-mail-tag"
                                    type="button"
                                    title="{{ $tag['label'] }}"
                                    data-mail-placeholder-token="{{ $tag['token'] }}"
                                >
                                    <span>{{ $tag['label'] }}</span>
                                    <small>{{ $tag['token'] }}</small>
                                </button>
                            @empty
                                <span class="response-mail-empty">{{ __('Geen tags beschikbaar') }}</span>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </aside>

            <div class="response-mail-list">
                @foreach ($messages as $index => $mail)
                    <article class="response-mail-card" wire:key="response-mail-card-{{ $mail['key'] }}">
                        <header class="response-mail-card-header">
                            <div>
                                <span class="response-mail-card-kicker">{{ __('Mail') }} {{ $index + 1 }}</span>
                                <h3>{{ $mail['name'] ?: __('Response mail') }}</h3>
                            </div>
                            <div class="response-mail-card-actions">
                                <button class="btn btn-icon-only" type="button" wire:click="moveMessageUp({{ $index }})" @disabled($index === 0) title="{{ __('Omhoog verplaatsen') }}">
                                    <x-admin.material-icon name="keyboard_arrow_up" />
                                </button>
                                <button class="btn btn-icon-only" type="button" wire:click="moveMessageDown({{ $index }})" @disabled($loop->last) title="{{ __('Omlaag verplaatsen') }}">
                                    <x-admin.material-icon name="keyboard_arrow_down" />
                                </button>
                                <button class="btn btn-duplicate btn-icon-only" type="button" wire:click="duplicateMessage({{ $index }})" title="{{ __('Dupliceren') }}">
                                    <x-admin.material-icon name="content_copy" />
                                </button>
                                <button class="btn btn-remove btn-icon-only" type="button" wire:click="removeMessage({{ $index }})" title="{{ __('Verwijderen') }}">
                                    <x-admin.material-icon name="delete" />
                                </button>
                            </div>
                        </header>

                        <input type="hidden" wire:model="messages.{{ $index }}.id">
                        <input type="hidden" wire:model="messages.{{ $index }}.type">

                        <div class="response-mail-card-grid">
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="response-mail-name-{{ $mail['key'] }}">{{ __('Naam') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="response-mail-name-{{ $mail['key'] }}" type="text" wire:model.blur="messages.{{ $index }}.name">
                                    @error("messages.{$index}.name")
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="response-mail-layout-{{ $mail['key'] }}">{{ __('Layout') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <select id="response-mail-layout-{{ $mail['key'] }}" wire:model.live="messages.{{ $index }}.layout">
                                        @foreach ($layoutOptions as $layout => $label)
                                            <option value="{{ $layout }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error("messages.{$index}.layout")
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="response-mail-subject-{{ $mail['key'] }}">{{ __('Onderwerp') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input
                                    id="response-mail-subject-{{ $mail['key'] }}"
                                    type="text"
                                    wire:model.live.debounce.500ms="messages.{{ $index }}.subject"
                                    data-mail-builder-insertable
                                >
                                @error("messages.{$index}.subject")
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="response-mail-body-{{ $mail['key'] }}">{{ __('Bericht') }}</label>
                            </div>
                            <div class="form-item-input">
                                <textarea
                                    id="response-mail-body-{{ $mail['key'] }}"
                                    wire:model.live.debounce.500ms="messages.{{ $index }}.body"
                                    data-mail-builder-insertable
                                ></textarea>
                                @error("messages.{$index}.body")
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <label class="response-mail-active-option">
                            <input type="checkbox" wire:model.live="messages.{{ $index }}.is_active">
                            <span class="checkbox"></span>
                            {{ __('Actief') }}
                        </label>
                    </article>
                @endforeach
            </div>
        </div>
    </form>
</div>
