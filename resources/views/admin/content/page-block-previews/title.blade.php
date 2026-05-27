@php($blockData = $data ?? [])
@php($blockSettings = $settings ?? [])

<div class="page-block-editor-preview">
    @if (filled(data_get($blockData, 'title')))
        @include('components.page-blocks.title', ['data' => $blockData, 'settings' => $blockSettings])
    @else
        <span class="page-block-editor-preview-empty">{{ __('Untitled title block') }}</span>
    @endif
</div>
