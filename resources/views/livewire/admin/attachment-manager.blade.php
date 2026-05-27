<div class="attachment-manager attachment-manager-livewire">
    @if ($message)
        <div class="attachment-livewire-message" data-flash-message>
            <span>{{ $message }}</span>
            <button type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    <div class="attachment-upload-panel">
        <div class="attachment-upload-header">
            <h3 class="sub-title">{{ __('Bijlage(n) toevoegen') }}</h3>
            <button
                class="attachment-info-button"
                type="button"
                wire:click="toggleCapacity"
                aria-expanded="{{ $capacityVisible ? 'true' : 'false' }}"
                aria-controls="attachment-capacity-{{ $module }}-{{ $recordId }}"
                title="{{ __('Uploadcapaciteit') }}"
            >
                <x-admin.material-icon name="info" />
            </button>
        </div>

        <label class="attachment-dropzone" for="attachment-files-{{ $module }}-{{ $recordId }}-{{ $fileInputKey }}">
            <input
                class="attachment-file-input"
                id="attachment-files-{{ $module }}-{{ $recordId }}-{{ $fileInputKey }}"
                multiple
                type="file"
                wire:key="attachment-files-{{ $module }}-{{ $recordId }}-{{ $fileInputKey }}"
                wire:model="incomingUploads"
            >
            <span class="attachment-dropzone-label">
                <x-admin.material-icon class="attachment-dropzone-icon" name="upload_file" />
                <span class="attachment-dropzone-title">{{ __('Sleep bestanden hierheen of kies bestanden') }}</span>
                <span class="attachment-dropzone-text">{{ __('Nieuwe selecties worden aan de wachtrij toegevoegd.') }}</span>
            </span>
        </label>

        @if ($capacityVisible)
            <div id="attachment-capacity-{{ $module }}-{{ $recordId }}" class="attachment-capacity" aria-label="{{ __('Uploadcapaciteit') }}">
                <strong class="attachment-capacity-title">{{ __('Uploadcapaciteit') }}</strong>
                <dl class="attachment-capacity-list">
                    <div>
                        <dt>{{ __('Aantal') }}</dt>
                        <dd>{{ trans_choice('{1} Maximaal :count bestand|[2,*] Maximaal :count bestanden', $uploadCapacity['max_files'], ['count' => $uploadCapacity['max_files']]) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('Bestandsgrootte') }}</dt>
                        <dd>{{ __('Maximaal :size per bestand', ['size' => $uploadCapacity['max_file_size']]) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('Totale batch') }}</dt>
                        <dd>{{ __('Tot :size per upload', ['size' => $uploadCapacity['max_batch_size']]) }}</dd>
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

        @error('incomingUploads')
            <p class="attachment-error">{{ $message }}</p>
        @enderror

        @error('incomingUploads.*')
            <p class="attachment-error">{{ $message }}</p>
        @enderror

        @error('queuedUploads')
            <p class="attachment-error">{{ $message }}</p>
        @enderror

        @error('queuedUploads.*')
            <p class="attachment-error">{{ $message }}</p>
        @enderror

        @if ($queuedUploads !== [])
            <div class="attachment-selection-list">
                @foreach ($queuedUploads as $upload)
                    <div class="attachment-selection-item" wire:key="queued-attachment-{{ $loop->index }}-{{ $upload->getFilename() }}">
                        <x-admin.material-icon class="attachment-selection-icon" name="attach_file" />
                        <div class="attachment-selection-content">
                            <span class="attachment-selection-title">{{ $upload->getClientOriginalName() }}</span>
                            <span class="attachment-selection-meta">{{ \Illuminate\Support\Number::fileSize((int) $upload->getSize()) }}</span>
                            <label class="u-sr-only" for="queued-attachment-name-{{ $module }}-{{ $recordId }}-{{ $loop->index }}">{{ __('Bijlage naam') }}</label>
                            <input
                                class="attachment-selection-name"
                                id="queued-attachment-name-{{ $module }}-{{ $recordId }}-{{ $loop->index }}"
                                type="text"
                                wire:model.blur="queuedNames.{{ $loop->index }}"
                            >
                        </div>
                        <button class="attachment-item-button" type="button" wire:click="removeQueuedUpload({{ $loop->index }})" aria-label="{{ __('Verwijder uit wachtrij') }}" title="{{ __('Verwijder uit wachtrij') }}">
                            <x-admin.material-icon name="close" />
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="attachment-upload-actions">
            <button class="btn btn-add" type="button" wire:click="uploadAttachments" wire:loading.attr="disabled" wire:target="incomingUploads,uploadAttachments" @disabled($queuedUploads === [])>
                <x-admin.material-icon name="cloud_upload" />
                <span wire:loading.remove wire:target="uploadAttachments">{{ __('Upload nu') }}</span>
                <span wire:loading wire:target="uploadAttachments">{{ __('Uploaden...') }}</span>
            </button>
        </div>

        <div class="attachment-upload-status" wire:loading.flex wire:target="incomingUploads" role="status" aria-live="polite" aria-busy="true">
            <span class="attachment-loader" aria-hidden="true"></span>
            <span class="attachment-upload-status-copy">
                <strong>{{ __('Bestanden voorbereiden') }}</strong>
                <span>{{ __('De bestanden worden gecontroleerd en aan de wachtrij toegevoegd.') }}</span>
            </span>
        </div>

        <div class="attachment-upload-status" wire:loading.flex wire:target="uploadAttachments" role="status" aria-live="polite" aria-busy="true">
            <span class="attachment-loader" aria-hidden="true"></span>
            <span class="attachment-upload-status-copy">
                <strong>{{ __('Bijlagen opslaan') }}</strong>
                <span>{{ __('De bestanden worden geupload en direct aan dit item gekoppeld.') }}</span>
            </span>
        </div>
    </div>

    <div class="attachment-list-panel">
        <h3 class="sub-title">{{ __('Reeds toegevoegde bijlage(n)') }}</h3>

        @if ($attachments->isNotEmpty())
            <div class="attachment-sortable-list" data-drag-container data-drag-layout="list" data-livewire-sort-method="moveAttachment" aria-label="{{ __('Bijlagen sorteren') }}">
                @foreach ($attachments as $attachment)
                    <div
                        class="attachment-sortable-item"
                        data-drag-item
                        data-drag-id="{{ $attachment->id }}"
                        wire:key="saved-attachment-{{ $module }}-{{ $attachment->id }}"
                    >
                        <button class="attachment-drag-handle" draggable="true" type="button" aria-label="{{ __('Sleep om te sorteren') }}" title="{{ __('Sleep om te sorteren') }}">
                            <x-admin.material-icon name="drag_indicator" />
                        </button>

                        <div class="attachment-item-content">
                            <label class="u-sr-only" for="attachment-name-{{ $module }}-{{ $attachment->id }}">{{ __('Naam') }}</label>
                            <input
                                class="attachment-item-name"
                                id="attachment-name-{{ $module }}-{{ $attachment->id }}"
                                type="text"
                                wire:blur="saveAttachment({{ $attachment->id }})"
                                wire:model.blur="attachmentForms.{{ $attachment->id }}.name"
                            >
                            <span class="attachment-item-meta">{{ basename((string) $attachment->url) }}</span>
                        </div>

                        <div class="attachment-item-actions">
                            <a class="attachment-item-button" href="{{ asset($attachment->url) }}" target="_blank" rel="noreferrer" title="{{ __('Bekijk de bijlage') }}">
                                <x-admin.material-icon name="open_in_new" />
                            </a>
                            <button class="attachment-item-button attachment-delete-button" type="button" wire:click="deleteAttachment({{ $attachment->id }})" wire:confirm="{{ __('Deze bijlage verwijderen?') }}" title="{{ __('Verwijder de bijlage') }}">
                                <x-admin.material-icon name="delete" />
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="attachment-message">
                <x-admin.material-icon name="info" />
                <em>{{ __('Er zijn (nog) geen bijlagen toegevoegd') }}.</em>
            </div>
        @endif
    </div>
</div>
