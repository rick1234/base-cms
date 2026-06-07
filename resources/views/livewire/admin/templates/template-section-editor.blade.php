<div class="template-section-editor" wire:loading.class="is-loading">
    @if ($message)
        <div class="content-album-message template-section-message" data-flash-message>
            <span>{{ $message }}</span>
            <button type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    <div class="template-section-layout">
        <section class="template-section-panel" aria-labelledby="template-sections-title">
            <div class="template-section-toolbar">
                <div>
                    <h2 id="template-sections-title" class="title">{{ __('Defined sections') }}</h2>
                    <p class="form-item-description">{{ __('Define frontend template positions that modules can fill, such as a homepage block, footer banner, or catalog upsell banner.') }}</p>
                </div>
                <button class="btn btn-add" type="button" wire:click="addSection">
                    <x-admin.material-icon name="add" />
                    {{ __('Section toevoegen') }}
                </button>
            </div>

            <div class="template-section-list">
                <div class="template-section-row header">
                    <div>{{ __('Name') }}</div>
                    <div>{{ __('Technical name') }}</div>
                    <div>{{ __('Type') }}</div>
                    <div>{{ __('Options') }}</div>
                </div>

                @foreach ($sections as $index => $section)
                    <div class="template-section-row" wire:key="template-section-row-{{ $index }}">
                        <div>
                            <label class="content-admin-screen-label" for="section_{{ $index }}_label">{{ __('Name') }}</label>
                            <input id="section_{{ $index }}_label" type="text" wire:model.live.debounce.300ms="sections.{{ $index }}.label" placeholder="{{ __('Homepage Right Block') }}">
                            @error('sections.'.$index.'.label')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label class="content-admin-screen-label" for="section_{{ $index }}_handle">{{ __('Technical name') }}</label>
                            <input id="section_{{ $index }}_handle" type="text" wire:model.live.debounce.300ms="sections.{{ $index }}.handle" placeholder="homepage_right_block">
                            @error('sections.'.$index.'.handle')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label class="content-admin-screen-label" for="section_{{ $index }}_type">{{ __('Type') }}</label>
                            <select id="section_{{ $index }}_type" wire:model.live="sections.{{ $index }}.type">
                                <option value="banner">{{ __('Banner') }}</option>
                                <option value="mixed">{{ __('Mixed') }}</option>
                            </select>
                            @error('sections.'.$index.'.type')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="template-section-actions">
                            <button type="button" wire:click="moveSection({{ $index }}, 'up')" @disabled($loop->first) title="{{ __('Omhoog verplaatsen') }}">
                                <x-admin.material-icon name="keyboard_arrow_up" />
                            </button>
                            <button type="button" wire:click="moveSection({{ $index }}, 'down')" @disabled($loop->last) title="{{ __('Omlaag verplaatsen') }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </button>
                            <button type="button" wire:click="removeSection({{ $index }})" title="{{ __('Verwijderen') }}">
                                <x-admin.material-icon name="delete" />
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="template-section-footer">
                <button class="btn btn-save" type="button" wire:click="save" wire:loading.attr="disabled">
                    <x-admin.material-icon name="save" />
                    {{ __('Sections opslaan') }}
                </button>
            </div>
        </section>

        <x-admin.template-wireframe :template="$previewTemplate" />
    </div>
</div>
