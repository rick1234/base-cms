@php
    use App\Cms\PageBlocks\Support\PageBlockRenderer;

    $url = PageBlockRenderer::safeUrl(data_get($data, 'url'));
    $label = (string) data_get($data, 'label', '');
    $style = in_array(data_get($settings, 'style'), ['primary', 'secondary', 'text'], true) ? data_get($settings, 'style') : 'primary';
    $alignment = in_array(data_get($settings, 'alignment'), ['left', 'center', 'right'], true) ? data_get($settings, 'alignment') : 'left';
    $newTab = (bool) data_get($settings, 'open_in_new_tab', false);
@endphp

@if ($url && $label !== '')
    <div class="page-block-button-row page-block-button-row--{{ $alignment }}">
        <a class="page-block-button page-block-button--{{ $style }}" href="{{ $url }}" @if($newTab) target="_blank" rel="noopener" @endif>
            {{ $label }}
        </a>
    </div>
@endif
