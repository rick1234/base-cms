@php($blockData = $data ?? [])
@php($blockSettings = $settings ?? [])

<div class="page-block-editor-preview">
    @if (filled(data_get($blockData, 'file')))
        @include('components.page-blocks.attachment', ['data' => $blockData, 'settings' => $blockSettings])
    @else
        <span class="page-block-editor-preview-empty">{{ __('No attachment selected') }}</span>
    @endif
</div>
