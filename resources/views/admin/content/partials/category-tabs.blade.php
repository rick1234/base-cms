@php
    $id = $category?->id;
@endphp

<ul class="tabmenu">
    <li class="tabmenu-item info-button {{ $active === 'info' ? 'active' : '' }}">
        @if ($id)
            <a href="{{ route($routeNames['edit'], ['id' => $id]) }}">{{ __('Basis informatie') }}</a>
        @else
            {{ __('Basis informatie') }}
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
