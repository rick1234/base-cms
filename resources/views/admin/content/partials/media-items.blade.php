@if ($images->isNotEmpty())
    <div class="uploads-container">
        @foreach ($images as $image)
            <div class="upload-item" data-uploadid="{{ $image->id }}">
                <span class="upload-image">
                    @if ($image->image_path)
                        <img src="{{ asset($image->image_path) }}" alt="{{ $image->caption ?? __('Image') }}">
                    @endif
                </span>
                <span class="upload-buttons">
                    <span class="drag-icon">
                        <span class="flaticon-shuffle-mode-arrows"></span>
                    </span>
                    <form method="post" action="{{ route($deleteRoute, ['id' => $image->id]) }}">
                        @csrf
                        <button class="delete-icon" type="submit" title="{{ __('Verwijderen') }}">
                            <span class="flaticon-rubbish-bin-delete-button"></span>
                        </button>
                    </form>
                </span>
                <span class="upload-name">
                    <form method="post" action="{{ route($updateNameRoute, ['uploadId' => $image->id]) }}">
                        @csrf
                        <input name="uploadName" placeholder="{{ __('Titel') }}" type="text" value="{{ $image->caption }}">
                        <button class="save-upload-name" type="submit" title="{{ __('Opslaan') }}">
                            <span class="flaticon-save-button"></span>
                        </button>
                    </form>
                </span>
            </div>
        @endforeach
    </div>
@else
    <div class="attachment-message">
        <span class="flaticon-rounded-info-button"></span>
        <em>{{ __('Er zijn (nog) geen afbeeldingen toegevoegd') }}.</em>
    </div>
@endif
