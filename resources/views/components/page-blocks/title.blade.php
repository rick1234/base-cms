@php
    $level = in_array(data_get($data, 'level'), ['h2', 'h3', 'h4', 'h5', 'h6'], true) ? data_get($data, 'level') : 'h2';
    $title = (string) data_get($data, 'title', '');
    $alignment = in_array(data_get($settings, 'alignment'), ['left', 'center', 'right'], true) ? data_get($settings, 'alignment') : 'left';
    $anchor = preg_match('/^[A-Za-z][A-Za-z0-9\-_:.]*$/', (string) data_get($settings, 'anchor')) === 1 ? data_get($settings, 'anchor') : null;
@endphp

@if ($title !== '')
    @if ($level === 'h2')
        <h2 @if($anchor) id="{{ $anchor }}" @endif class="page-block-title page-block-title--{{ $alignment }}">{{ $title }}</h2>
    @elseif ($level === 'h3')
        <h3 @if($anchor) id="{{ $anchor }}" @endif class="page-block-title page-block-title--{{ $alignment }}">{{ $title }}</h3>
    @elseif ($level === 'h4')
        <h4 @if($anchor) id="{{ $anchor }}" @endif class="page-block-title page-block-title--{{ $alignment }}">{{ $title }}</h4>
    @elseif ($level === 'h5')
        <h5 @if($anchor) id="{{ $anchor }}" @endif class="page-block-title page-block-title--{{ $alignment }}">{{ $title }}</h5>
    @else
        <h6 @if($anchor) id="{{ $anchor }}" @endif class="page-block-title page-block-title--{{ $alignment }}">{{ $title }}</h6>
    @endif
@endif
