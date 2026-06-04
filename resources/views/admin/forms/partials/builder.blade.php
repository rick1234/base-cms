@if (! $isExisting)
    <div class="attachment-message">
        <x-admin.material-icon name="info" />
        <em>{{ __('Sla het formulier eerst op voordat u velden toevoegt.') }}</em>
    </div>
@else
    <form
        id="form-builder-form"
        method="post"
        action="{{ route($routeNames['builder.save'], ['id' => $form->id]) }}"
        data-form-builder
        data-field-types='@json($fieldTypes)'
        data-field-icons='@json($fieldElementIcons)'
        data-initial-blocks='@json($builderBlocks)'
        data-form-builder-labels='@json($builderLabels)'
    >
        @csrf
        <input type="hidden" name="id" value="{{ $form->id }}">

        <div data-form-builder-inputs></div>

        <div class="form-builder-workspace">
            <aside class="form-builder-palette" aria-label="{{ __('Form elements') }}">
                <h2 class="form-builder-panel-title">{{ __('Form elements') }}</h2>
                <div class="form-builder-element-list">
                    @foreach ($fieldTypes as $type => $label)
                        <button class="form-builder-element" type="button" draggable="true" data-form-builder-add="{{ $type }}">
                            <x-admin.material-icon :name="$fieldElementIcons[$type] ?? 'input'" />
                            <span>{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </aside>

            <section class="form-builder-canvas-panel" aria-labelledby="form-builder-canvas-title">
                <div class="form-builder-canvas-heading">
                    <h2 class="form-builder-panel-title" id="form-builder-canvas-title">{{ __('Formulieropbouw') }}</h2>
                </div>

                <ol class="form-builder-canvas" data-form-builder-canvas></ol>

                <div class="form-builder-empty" data-form-builder-empty>
                    {{ __('Nog geen velden') }}
                </div>
            </section>
        </div>

        <div class="form-builder-modal" role="dialog" aria-modal="true" aria-labelledby="form-builder-modal-title" hidden data-form-builder-modal>
            <div class="form-builder-modal-backdrop" data-form-builder-close></div>
            <div class="form-builder-modal-panel">
                <div class="form-builder-modal-header">
                    <h2 class="title" id="form-builder-modal-title">{{ __('Veld instellingen') }}</h2>
                    <button class="config-button" type="button" aria-label="{{ __('Close') }}" data-form-builder-close>
                        <x-admin.material-icon name="close" />
                    </button>
                </div>

                <div class="grid">
                    <div class="grid-row">
                        <div class="col-6">
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="form_builder_field_label">{{ __('Label') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="form_builder_field_label" type="text" data-form-builder-setting="label">
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="form_builder_field_name">{{ __('Naam') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="form_builder_field_name" type="text" data-form-builder-setting="name">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid-row">
                        <div class="col-6">
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="form_builder_field_type">{{ __('Type') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <select id="form_builder_field_type" data-form-builder-setting="type">
                                        @foreach ($fieldTypes as $type => $label)
                                            <option value="{{ $type }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="form_builder_field_width">{{ __('Breedte') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="form_builder_field_width" type="number" min="10" max="100" step="5" data-form-builder-setting="width">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-item">
                    <div class="form-item-label">
                        <label for="form_builder_field_placeholder">{{ __('Placeholder') }}</label>
                    </div>
                    <div class="form-item-input">
                        <input id="form_builder_field_placeholder" type="text" data-form-builder-setting="placeholder">
                    </div>
                </div>

                <div class="form-item">
                    <div class="form-item-label">
                        <label for="form_builder_field_help">{{ __('Helptekst') }}</label>
                    </div>
                    <div class="form-item-input">
                        <textarea id="form_builder_field_help" data-form-builder-setting="help_text"></textarea>
                    </div>
                </div>

                <div class="grid">
                    <div class="grid-row">
                        <div class="col-6">
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="form_builder_field_validation">{{ __('Validatie') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="form_builder_field_validation" type="text" data-form-builder-setting="validation_rules">
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="form_builder_field_default">{{ __('Standaardwaarde') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <input id="form_builder_field_default" type="text" data-form-builder-setting="default_value">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-builder-modal-checks">
                    <label>
                        <input type="checkbox" data-form-builder-setting="is_required">
                        <span class="checkbox"></span>
                        {{ __('Verplicht') }}
                    </label>
                    <label>
                        <input type="checkbox" data-form-builder-setting="label_visible">
                        <span class="checkbox"></span>
                        {{ __('Label tonen') }}
                    </label>
                </div>

                <div class="form-item" data-form-builder-options-panel>
                    <div class="form-item-label">
                        <label for="form_builder_field_options">{{ __('Opties') }}</label>
                    </div>
                    <div class="form-item-input">
                        <textarea id="form_builder_field_options" data-form-builder-setting="options_text"></textarea>
                    </div>
                </div>

                <div class="form-builder-modal-actions">
                    <button class="btn btn-save" type="button" data-form-builder-save-settings>
                        <x-admin.material-icon name="save" />
                        {{ __('Instellingen opslaan') }}
                    </button>
                    <button class="btn btn-remove" type="button" data-form-builder-delete-field>
                        <x-admin.material-icon name="delete" />
                        {{ __('Veld verwijderen') }}
                    </button>
                    <button class="btn btn-cancel" type="button" data-form-builder-close>
                        <x-admin.material-icon name="undo" />
                        {{ __('Annuleren') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
@endif
