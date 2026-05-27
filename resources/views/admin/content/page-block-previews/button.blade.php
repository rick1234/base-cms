@php($blockData = $data ?? [])
@php($blockSettings = $settings ?? [])

<div class="page-block-editor-preview">
    @if (filled(data_get($blockData, 'label')) && filled(data_get($blockData, 'url')))
        @include('components.page-blocks.button', ['data' => $blockData, 'settings' => $blockSettings])
    @else
        <span class="page-block-editor-preview-empty">{{ __('Empty button block') }}</span>
    @endif
</div>
