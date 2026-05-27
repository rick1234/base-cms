@php
    use App\Cms\PageBlocks\Support\PageBlockRenderer;

    $fileUrl = PageBlockRenderer::mediaUrl(data_get($data, 'file'));
    $title = (string) (data_get($data, 'display_title') ?: basename((string) data_get($data, 'file')));
    $description = (string) data_get($data, 'description', '');
    $buttonLabel = (string) (data_get($data, 'button_label') ?: __('Download'));
    $newTab = (bool) data_get($settings, 'open_in_new_tab', false);
@endphp

@if ($fileUrl)
    <div class="page-block-attachment">
        <div class="page-block-attachment-content">
            @if ($title !== '')
                <strong>{{ $title }}</strong>
            @endif

            @if ($description !== '')
                <p>{{ $description }}</p>
            @endif
        </div>
        <a class="page-block-button page-block-button--secondary" href="{{ $fileUrl }}" @if($newTab) target="_blank" rel="noopener" @endif>
            {{ $buttonLabel }}
        </a>
    </div>
@endif
