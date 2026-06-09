<div class="catalog-video-editor">
    @if ($message)
        <div class="content-album-message catalog-video-editor-message is-{{ $messageLevel }}" data-flash-message>
            <span>{{ $message }}</span>
            <button type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    <form id="catalog-videos-form" wire:submit.prevent="save">
        <div class="response-mail-builder-toolbar catalog-video-editor-toolbar">
            <div class="response-mail-builder-title">
                <h2 class="title">{{ __('Videos') }}</h2>
                <span>{{ trans_choice('{0} Geen videos|{1} :count video|[2,*] :count videos', count($videos), ['count' => count($videos)]) }}</span>
            </div>
            <div class="response-mail-builder-actions">
                <button class="btn btn-save" type="submit" wire:loading.attr="disabled" wire:target="save">
                    <x-admin.material-icon name="save" />
                    <span wire:loading.remove wire:target="save">{{ __('Videos opslaan') }}</span>
                    <span wire:loading wire:target="save">{{ __('Opslaan...') }}</span>
                </button>
                <button class="btn btn-add" type="button" wire:click="addVideo">
                    <x-admin.material-icon name="add" />
                    {{ __('Video toevoegen') }}
                </button>
            </div>
        </div>

        <div class="catalog-video-editor-grid">
            <aside class="response-mail-tag-panel catalog-video-help" aria-labelledby="catalog-video-help-title">
                <h2 id="catalog-video-help-title" class="title">{{ __('Video content') }}</h2>
                <div class="response-mail-tag-group">
                    <h3>{{ __('Bron') }}</h3>
                    <p>{{ __('Gebruik een volledige URL van YouTube, Vimeo of een externe videopagina.') }}</p>
                </div>
                <div class="response-mail-tag-group">
                    <h3>{{ __('Volgorde') }}</h3>
                    <p>{{ __('De volgorde van de kaarten bepaalt de volgorde op de website en in de API.') }}</p>
                </div>
            </aside>

            <div class="response-mail-list catalog-video-list">
                @foreach ($videos as $index => $video)
                    <article class="response-mail-card catalog-video-card" wire:key="catalog-video-card-{{ $video['key'] }}">
                        <header class="response-mail-card-header">
                            <div>
                                <span class="response-mail-card-kicker">{{ __('Video') }} {{ $index + 1 }}</span>
                                <h3>{{ filled($video['title'] ?? null) ? $video['title'] : __('Product video') }}</h3>
                            </div>
                            <div class="response-mail-card-actions">
                                <button class="btn btn-icon-only" type="button" wire:click="moveVideoUp({{ $index }})" @disabled($index === 0) title="{{ __('Omhoog verplaatsen') }}">
                                    <x-admin.material-icon name="keyboard_arrow_up" />
                                </button>
                                <button class="btn btn-icon-only" type="button" wire:click="moveVideoDown({{ $index }})" @disabled($loop->last) title="{{ __('Omlaag verplaatsen') }}">
                                    <x-admin.material-icon name="keyboard_arrow_down" />
                                </button>
                                <button class="btn btn-duplicate btn-icon-only" type="button" wire:click="duplicateVideo({{ $index }})" title="{{ __('Dupliceren') }}">
                                    <x-admin.material-icon name="content_copy" />
                                </button>
                                <button class="btn btn-remove btn-icon-only" type="button" wire:click="removeVideo({{ $index }})" title="{{ __('Verwijderen') }}">
                                    <x-admin.material-icon name="delete" />
                                </button>
                            </div>
                        </header>

                        <input type="hidden" wire:model="videos.{{ $index }}.id">

                        <div class="response-mail-card-grid">
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="catalog-video-title-{{ $video['key'] }}">{{ __('Titel') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="catalog-video-title-{{ $video['key'] }}" type="text" wire:model.live.debounce.500ms="videos.{{ $index }}.title">
                                    @error("videos.{$index}.title")
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="catalog-video-provider-{{ $video['key'] }}">{{ __('Provider') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <select id="catalog-video-provider-{{ $video['key'] }}" wire:model.live="videos.{{ $index }}.provider">
                                        @foreach ($providers as $provider => $label)
                                            <option value="{{ $provider }}">{{ __($label) }}</option>
                                        @endforeach
                                    </select>
                                    @error("videos.{$index}.provider")
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="catalog-video-url-{{ $video['key'] }}">{{ __('Video URL') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="catalog-video-url-{{ $video['key'] }}" type="url" wire:model.live.debounce.500ms="videos.{{ $index }}.url">
                                @error("videos.{$index}.url")
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        @if (filled($video['url'] ?? null))
                            <div class="catalog-video-preview">
                                <x-admin.material-icon name="play_circle" />
                                <a href="{{ $video['url'] }}" target="_blank" rel="noopener noreferrer">{{ __('Video openen') }}</a>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </form>
</div>
