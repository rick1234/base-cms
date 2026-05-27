@php($blockData = $data ?? [])
@php($blockSettings = $settings ?? [])

<div class="page-block-editor-preview">
    @if (filled(data_get($blockData, 'images')))
        @include('components.page-blocks.gallery', ['data' => $blockData, 'settings' => $blockSettings])
    @else
        <span class="page-block-editor-preview-empty">{{ __('No gallery images selected') }}</span>
    @endif
</div>
