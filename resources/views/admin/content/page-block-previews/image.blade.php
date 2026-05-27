@php($blockData = $data ?? [])
@php($blockSettings = $settings ?? [])

<div class="page-block-editor-preview">
    @if (filled(data_get($blockData, 'image')))
        @include('components.page-blocks.image', ['data' => $blockData, 'settings' => $blockSettings])
    @else
        <span class="page-block-editor-preview-empty">{{ __('No image selected') }}</span>
    @endif
</div>
