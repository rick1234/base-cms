@php
    use App\Cms\PageBlocks\Support\PageBlockRenderer;

    $alignment = in_array(data_get($settings, 'alignment'), ['left', 'center', 'right'], true) ? data_get($settings, 'alignment') : 'left';
    $background = in_array(data_get($settings, 'background_style'), ['none', 'muted', 'accent'], true) ? data_get($settings, 'background_style') : 'none';
    $intro = (bool) data_get($settings, 'intro_style', false);
@endphp

<div class="page-block-text page-block-text--{{ $alignment }} page-block-text--background-{{ $background }} @if($intro) page-block-text--intro @endif">
    {{ PageBlockRenderer::sanitizedHtml(data_get($data, 'content')) }}
</div>
