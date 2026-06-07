<div class="content-album {{ trim($albumClass ?? '') }}">
    @if ($message)
        <div class="content-album-message flash-message flash-message-success" data-flash-message role="alert">
            <div class="flash-message-content">
                <span>{{ $message }}</span>
            </div>
            <button class="flash-message-close" type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    <section class="content-album-upload" aria-labelledby="content-album-upload-title">
        <div class="content-album-upload-header">
            <h2 id="content-album-upload-title" class="title">{{ __('Selecteer bestanden') }}</h2>
            <button
                class="content-album-info-button"
                type="button"
                wire:click="toggleCapacity"
                aria-expanded="{{ $capacityVisible ? 'true' : 'false' }}"
                aria-controls="content-album-capacity"
                title="{{ __('Uploadcapaciteit') }}"
            >
                <x-admin.material-icon name="info" />
            </button>
        </div>

        <form
            action="{{ $imageUploadRoute }}"
            class="content-album-upload-form"
            data-content-image-editor
            data-edited-file-suffix="{{ __('edited') }}"
            data-export-error-message="{{ __('The edited image could not be prepared. Try another file or crop.') }}"
            data-files-selected-plural="{{ __('Bestanden klaar voor upload') }}"
            data-files-selected-singular="{{ __('Bestand klaar voor upload') }}"
            data-processing-copy="{{ __('De uitsnede wordt verwerkt.') }}"
            data-processing-title="{{ __('Afbeelding voorbereiden') }}"
            data-upload-error-message="{{ __('Image upload failed. Check the file and try again.') }}"
            data-uploading-copy="{{ __('De bewerkte afbeelding wordt opgeslagen.') }}"
            data-uploading-title="{{ __('Afbeelding opslaan') }}"
            data-upload-success-message="{{ __('Image uploaded.') }}"
            data-upload-url="{{ $imageUploadRoute }}"
            enctype="multipart/form-data"
            method="post"
        >
            @csrf

            <label class="content-album-dropzone" for="content-album-uploads">
                <input
                    id="content-album-uploads"
                    class="content-album-file-input"
                    data-content-image-editor-input
                    name="images[]"
                    type="file"
                    accept="image/*"
                    multiple
                >
                <x-admin.material-icon class="content-album-dropzone-icon" name="add_photo_alternate" />
                <span class="content-album-dropzone-title">{{ __('Sleep afbeeldingen hierheen of kies bestanden') }}</span>
                <span class="content-album-dropzone-copy">{{ __('Kies een afbeelding om te bewerken of meerdere afbeeldingen om direct te uploaden.') }}</span>
            </label>

            <div class="content-album-upload-preview" data-content-image-editor-file-list hidden></div>

            <section class="content-image-editor" data-content-image-editor-panel hidden aria-label="{{ __('Afbeelding bewerken') }}">
                <div class="content-image-editor-header">
                    <div>
                        <strong>{{ __('Afbeelding bewerken') }}</strong>
                        <span data-content-image-editor-file-name></span>
                    </div>
                    <button class="content-album-icon-button" type="button" data-content-image-editor-action="close" aria-label="{{ __('Sluiten') }}" title="{{ __('Sluiten') }}">
                        <x-admin.material-icon name="close" />
                    </button>
                </div>

                <div class="content-image-editor-toolbar" role="toolbar" aria-label="{{ __('Afbeelding bewerken') }}">
                    <button class="content-image-editor-button" type="button" data-content-image-editor-action="rotate-left" title="{{ __('Links draaien') }}">
                        <x-admin.material-icon name="rotate_left" />
                    </button>
                    <button class="content-image-editor-button" type="button" data-content-image-editor-action="rotate-right" title="{{ __('Rechts draaien') }}">
                        <x-admin.material-icon name="rotate_right" />
                    </button>
                    <button class="content-image-editor-button" type="button" data-content-image-editor-action="zoom-out" title="{{ __('Uitzoomen') }}">
                        <x-admin.material-icon name="zoom_out" />
                    </button>
                    <button class="content-image-editor-button" type="button" data-content-image-editor-action="zoom-in" title="{{ __('Inzoomen') }}">
                        <x-admin.material-icon name="zoom_in" />
                    </button>
                    <button class="content-image-editor-button" type="button" data-content-image-editor-action="reset" title="{{ __('Reset') }}">
                        <x-admin.material-icon name="restart_alt" />
                    </button>
                    <label class="content-image-editor-ratio">
                        <span>{{ __('Uitsnede') }}</span>
                        <select data-content-image-editor-ratio>
                            <option value="free">{{ __('Vrij') }}</option>
                            <option value="16:9">16:9</option>
                            <option value="4:3">4:3</option>
                            <option value="1:1">1:1</option>
                            <option value="3:4">3:4</option>
                        </select>
                    </label>
                </div>

                <div class="content-image-editor-stage" data-content-image-editor-cropper></div>

                <div class="content-image-editor-actions">
                    <button class="btn btn-save" type="button" data-content-image-editor-upload>
                        <x-admin.material-icon name="cloud_upload" />
                        {{ __('Bewerking uploaden') }}
                    </button>
                    <button class="btn btn-cancel" type="button" data-content-image-editor-action="close">
                        <x-admin.material-icon name="close" />
                        {{ __('Annuleren') }}
                    </button>
                </div>
            </section>

            <div class="content-album-upload-status" data-content-image-editor-status hidden role="status" aria-live="polite">
                <span class="content-album-loader" aria-hidden="true"></span>
                <span class="content-album-upload-status-copy">
                    <strong data-content-image-editor-status-title>{{ __('Afbeelding voorbereiden') }}</strong>
                    <span data-content-image-editor-status-copy>{{ __('De uitsnede wordt verwerkt.') }}</span>
                </span>
            </div>

            @if ($capacityVisible)
                <div id="content-album-capacity" class="content-album-capacity" aria-label="{{ __('Uploadcapaciteit') }}">
                    <strong class="content-album-capacity-title">{{ __('Uploadcapaciteit') }}</strong>
                    <dl class="content-album-capacity-list">
                        <div>
                            <dt>{{ __('Aantal') }}</dt>
                            <dd>{{ trans_choice('{1} Maximaal :count afbeelding per upload|[2,*] Maximaal :count afbeeldingen per upload', $uploadCapacity['max_files'], ['count' => $uploadCapacity['max_files']]) }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('Bestandsgrootte') }}</dt>
                            <dd>{{ __('Maximaal :size per afbeelding', ['size' => $uploadCapacity['max_file_size']]) }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('Totale batch') }}</dt>
                            <dd>{{ __('Tot :size per selectie', ['size' => $uploadCapacity['max_batch_size']]) }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('Bestandstypes') }}</dt>
                            <dd>{{ $uploadCapacity['formats'] }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('Tijdvenster') }}</dt>
                            <dd>{{ trans_choice('{1} Afronden binnen :count minuut|[2,*] Afronden binnen :count minuten', $uploadCapacity['max_upload_time'], ['count' => $uploadCapacity['max_upload_time']]) }}</dd>
                        </div>
                    </dl>
                </div>
            @endif

            @error('uploads')
                <p class="content-album-error">{{ $message }}</p>
            @enderror

            @error('uploads.*')
                <p class="content-album-error">{{ $message }}</p>
            @enderror

            @error('images')
                <p class="content-album-error">{{ $message }}</p>
            @enderror

            @error('images.*')
                <p class="content-album-error">{{ $message }}</p>
            @enderror

            <button class="btn btn-add" type="submit">
                <x-admin.material-icon name="cloud_upload" />
                {{ __('Upload selectie') }}
            </button>
        </form>
    </section>

    <section class="content-album-library" aria-labelledby="content-album-library-title">
        <div class="content-album-library-header">
            <h3 id="content-album-library-title" class="sub-title">{{ __('Afbeeldingen') }}</h3>
            <span>{{ trans_choice('{0} Geen afbeeldingen|{1} :count afbeelding|[2,*] :count afbeeldingen', $images->count(), ['count' => $images->count()]) }}</span>
        </div>

        @if ($images->isNotEmpty())
            <div class="content-album-grid" data-drag-container data-drag-layout="grid" data-livewire-sort-method="moveImage">
                @foreach ($images as $image)
                    <article
                        class="content-album-card"
                        data-drag-item
                        data-drag-id="{{ $image->id }}"
                        wire:key="content-image-{{ $image->id }}"
                    >
                        <div class="content-album-preview">
                            @if ($image->image_path)
                                <img src="{{ asset($image->image_path) }}" alt="{{ $image->is_decorative ? '' : ($image->alt_text ?: $image->caption ?: __('Image')) }}">
                            @endif
                            <span class="content-album-drag-handle" draggable="true" title="{{ __('Sleep om te sorteren') }}">
                                <x-admin.material-icon name="drag_indicator" />
                            </span>
                        </div>

                        <div class="content-album-card-content">
                            <strong>{{ $image->caption ?: __('Naamloze afbeelding') }}</strong>
                            <span>{{ basename((string) $image->image_path) }}</span>
                        </div>

                        <div class="content-album-card-actions">
                            <button class="content-album-icon-button" type="button" wire:click="editImage({{ $image->id }})" aria-label="{{ __('Afbeelding bewerken') }}" title="{{ __('Afbeelding bewerken') }}">
                                <x-admin.material-icon name="edit" />
                            </button>
                            <button class="content-album-icon-button content-album-delete-button" type="button" wire:click="deleteImage({{ $image->id }})" wire:confirm="{{ __('Deze afbeelding verwijderen?') }}" aria-label="{{ __('Verwijderen') }}" title="{{ __('Verwijderen') }}">
                                <x-admin.material-icon name="delete" />
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="attachment-message">
                <x-admin.material-icon name="info" />
                <em>{{ __('Er zijn (nog) geen afbeeldingen toegevoegd') }}.</em>
            </div>
        @endif
    </section>

    @if ($editingImage)
        <div class="content-album-modal-backdrop" wire:click="closeImageEditor"></div>
        <section class="content-album-modal" role="dialog" aria-modal="true" aria-labelledby="content-album-editor-title">
            <header class="content-album-modal-header">
                <h3 id="content-album-editor-title" class="sub-title">{{ __('Afbeelding bewerken') }}</h3>
                <button class="content-album-icon-button" type="button" wire:click="closeImageEditor" aria-label="{{ __('Sluiten') }}" title="{{ __('Sluiten') }}">
                    <x-admin.material-icon name="close" />
                </button>
            </header>

            <form class="content-album-seo-form" wire:submit.prevent="saveImage({{ $editingImage->id }})">
                <div class="form-item">
                    <div class="form-item-label">
                        <label for="content-image-{{ $editingImage->id }}-caption">{{ __('Bijschrift') }}</label>
                    </div>
                    <div class="form-item-input">
                        <input id="content-image-{{ $editingImage->id }}-caption" type="text" wire:model="imageForms.{{ $editingImage->id }}.caption">
                        @error("imageForms.{$editingImage->id}.caption")
                            <p class="content-album-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="form-item">
                    <div class="form-item-label">
                        <label for="content-image-{{ $editingImage->id }}-alt-text">{{ __('Alt tekst') }}</label>
                    </div>
                    <div class="form-item-input">
                        <input id="content-image-{{ $editingImage->id }}-alt-text" type="text" wire:model="imageForms.{{ $editingImage->id }}.alt_text">
                        @error("imageForms.{$editingImage->id}.alt_text")
                            <p class="content-album-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="form-item">
                    <div class="form-item-label">
                        <label for="content-image-{{ $editingImage->id }}-title-text">{{ __('Afbeeldingstitel') }}</label>
                    </div>
                    <div class="form-item-input">
                        <input id="content-image-{{ $editingImage->id }}-title-text" type="text" wire:model="imageForms.{{ $editingImage->id }}.title_text">
                        @error("imageForms.{$editingImage->id}.title_text")
                            <p class="content-album-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="form-item">
                    <div class="form-item-label">
                        <label for="content-image-{{ $editingImage->id }}-description">{{ __('Beschrijving') }}</label>
                    </div>
                    <div class="form-item-input">
                        <textarea id="content-image-{{ $editingImage->id }}-description" rows="3" wire:model="imageForms.{{ $editingImage->id }}.description"></textarea>
                        @error("imageForms.{$editingImage->id}.description")
                            <p class="content-album-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="form-item">
                    <div class="form-item-label">
                        <label for="content-image-{{ $editingImage->id }}-credit">{{ __('Fotocredit') }}</label>
                    </div>
                    <div class="form-item-input">
                        <input id="content-image-{{ $editingImage->id }}-credit" type="text" wire:model="imageForms.{{ $editingImage->id }}.credit">
                        @error("imageForms.{$editingImage->id}.credit")
                            <p class="content-album-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <label class="content-album-checkbox">
                    <input type="checkbox" wire:model="imageForms.{{ $editingImage->id }}.is_decorative">
                    <span class="checkbox"></span>
                    {{ __('Decoratieve afbeelding, geen alt tekst gebruiken') }}
                </label>

                <div class="content-album-actions">
                    <button class="btn btn-save" type="submit" wire:loading.attr="disabled" wire:target="saveImage({{ $editingImage->id }})">
                        <x-admin.material-icon name="save" />
                        {{ __('Opslaan') }}
                    </button>
                    <button class="btn btn-cancel" type="button" wire:click="closeImageEditor">
                        <x-admin.material-icon name="close" />
                        {{ __('Annuleren') }}
                    </button>
                </div>
            </form>
        </section>
    @endif
</div>
