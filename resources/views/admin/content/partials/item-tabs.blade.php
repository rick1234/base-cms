@php
    $id = $contentItem?->id;
@endphp

<ul class="tabmenu">
    <li class="tabmenu-item info-button {{ $active === 'info' ? 'active' : '' }}">
        @if ($id)
            <a href="{{ route($routeNames['edit'], ['id' => $id]) }}">{{ __('Page') }}</a>
        @else
            {{ __('Page') }}
        @endif
    </li>
    <li class="tabmenu-item fotoalbum-button {{ $active === 'images' ? 'active' : '' }}">
        @if ($id)
            <a href="{{ route($routeNames['images'], ['id' => $id]) }}">{{ __('Fotoalbum') }}</a>
        @else
            {{ __('Fotoalbum') }}
        @endif
    </li>
    <li class="tabmenu-item form-button {{ $active === 'form' ? 'active' : '' }}">
        @if ($id)
            <a href="{{ route($routeNames['edit.tab'], ['id' => $id, 'tab' => 'form']) }}">{{ __('Formulier') }}</a>
        @else
            {{ __('Formulier') }}
        @endif
    </li>
    <li class="tabmenu-item seo-button {{ $active === 'seo' ? 'active' : '' }}">
        @if ($id)
            <a href="{{ route($routeNames['edit.tab'], ['id' => $id, 'tab' => 'seo']) }}">{{ __('SEO') }}</a>
        @else
            {{ __('SEO') }}
        @endif
    </li>
</ul>
