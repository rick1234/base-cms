@php($blockData = $data ?? [])
@php($blockSettings = $settings ?? [])

<div class="page-block-editor-preview">
    @if (filled(data_get($blockData, 'video_url')))
        @include('components.page-blocks.video', ['data' => $blockData, 'settings' => $blockSettings])
    @else
        <span class="page-block-editor-preview-empty">{{ __('No video URL set') }}</span>
    @endif
</div>
