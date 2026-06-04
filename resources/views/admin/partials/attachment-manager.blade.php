@php
    $attachments = collect($attachments ?? []);
    $inputId ??= 'attachment_files';
    $uploadTitle ??= __('Bijlage(n) toevoegen');
    $listTitle ??= __('Reeds toegevoegde bijlage(n)');
    $dropzoneTitle ??= __('Sleep bestanden hierheen of kies bestanden');
    $dropzoneText ??= __('Meerdere bestanden tegelijk uploaden');
    $namePlaceholder ??= __('Bijlage naam');
@endphp

<div class="attachment-manager" data-attachment-manager>
    <div class="attachment-upload-panel">
        <h3 class="sub-title">{{ $uploadTitle }}</h3>

        <div class="attachment-dropzone" data-attachment-dropzone>
            <input
                class="attachment-file-input"
                data-attachment-input
                id="{{ $inputId }}"
                multiple
                name="attachment_files[]"
                type="file"
            >
            <label class="attachment-dropzone-label" for="{{ $inputId }}">
                <x-admin.material-icon name="attach_file" class="attachment-dropzone-icon" />
                <span class="attachment-dropzone-title">{{ $dropzoneTitle }}</span>
                <span class="attachment-dropzone-text">{{ $dropzoneText }}</span>
            </label>
        </div>

        <div
            class="attachment-selection-list"
            data-attachment-selection-list
            data-name-placeholder="{{ $namePlaceholder }}"
            hidden
        ></div>

        @include('admin.content.partials.field-error', ['field' => 'attachment_files'])
    </div>

    <div class="attachment-list-panel">
        <h3 class="sub-title">{{ $listTitle }}</h3>

        @if ($attachments->isNotEmpty())
            <div class="attachment-sortable-list" data-attachment-sortable-list data-drag-container data-drag-layout="list" aria-label="{{ __('Bijlagen sorteren') }}">
                @foreach ($attachments as $attachment)
                    @php
                        $attachmentName = (string) old('existing_attachments.'.$attachment->id.'.name', $attachment->name);
                        $sortOrder = (int) old('existing_attachments.'.$attachment->id.'.sort_order', $loop->iteration);
                        $deleteInputId = 'attachment_delete_'.$inputId.'_'.$attachment->id;
                        $nameInputId = 'attachment_name_'.$inputId.'_'.$attachment->id;
                    @endphp

                    <div class="attachment-sortable-item" data-attachment-item data-drag-item data-attachment-id="{{ $attachment->id }}">
                        <button class="attachment-drag-handle" data-attachment-handle draggable="true" type="button" aria-label="{{ __('Sleep om te sorteren') }}" title="{{ __('Sleep om te sorteren') }}">
                            <x-admin.material-icon name="drag_indicator" />
                        </button>

                        <div class="attachment-item-content">
                            <label class="u-sr-only" for="{{ $nameInputId }}">{{ __('Naam') }}</label>
                            <input
                                class="attachment-item-name"
                                id="{{ $nameInputId }}"
                                name="existing_attachments[{{ $attachment->id }}][name]"
                                type="text"
                                value="{{ $attachmentName }}"
                            >
                            <input
                                data-attachment-sort-input
                                name="existing_attachments[{{ $attachment->id }}][sort_order]"
                                type="hidden"
                                value="{{ $sortOrder }}"
                            >
                            <span class="attachment-item-meta">{{ basename((string) $attachment->url) }}</span>
                        </div>

                        <div class="attachment-item-actions">
                            <a class="attachment-item-button" href="{{ asset($attachment->url) }}" target="_blank" rel="noreferrer" title="{{ __('Bekijk de bijlage') }}">
                                <x-admin.material-icon name="attach_file" />
                            </a>
                            <label class="attachment-item-button attachment-delete-button" for="{{ $deleteInputId }}" title="{{ __('Verwijder de bijlage') }}">
                                <input
                                    class="attachment-delete-input"
                                    data-attachment-delete-input
                                    id="{{ $deleteInputId }}"
                                    name="existing_attachments[{{ $attachment->id }}][delete]"
                                    type="checkbox"
                                    value="1"
                                    @checked(old('existing_attachments.'.$attachment->id.'.delete'))
                                >
                                <x-admin.material-icon name="close" />
                            </label>
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
