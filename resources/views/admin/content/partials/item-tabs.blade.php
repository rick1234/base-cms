@php
    $id = $contentItem?->id;
@endphp

<ul class="tabmenu">
    <li class="tabmenu-item info-button {{ $active === 'info' ? 'active' : '' }}">
        @if ($id)
            <a href="{{ route($routeNames['edit'], ['id' => $id]) }}">{{ __('Content item') }}</a>
        @else
            {{ __('Content item') }}
        @endif
    </li>
    <li class="tabmenu-item fotoalbum-button {{ $active === 'images' ? 'active' : '' }}">
        @if ($id)
            <a href="{{ route($routeNames['images'], ['id' => $id]) }}">{{ __('Fotoalbum') }}</a>
        @else
            {{ __('Fotoalbum') }}
        @endif
    </li>
    <li class="tabmenu-item slider-button {{ $active === 'slider' ? 'active' : '' }}">
        @if ($id)
            <a href="{{ route($routeNames['slider'], ['id' => $id]) }}">{{ __('Slider') }}</a>
        @else
            {{ __('Slider') }}
        @endif
    </li>
</ul>
