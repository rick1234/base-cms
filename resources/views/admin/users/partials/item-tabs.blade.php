@php
    $id = $managedUser?->id;
@endphp

<ul class="tabmenu">
    <li class="tabmenu-item info-button {{ $active === 'profile' ? 'active' : '' }}">
        @if ($id)
            <a href="{{ route($routeNames['edit'], ['id' => $id]) }}">{{ __('Profile') }}</a>
        @else
            {{ __('Profile') }}
        @endif
    </li>
    <li class="tabmenu-item access-button {{ $active === 'access' ? 'active' : '' }}">
        @if ($id)
            <a href="{{ route($routeNames['edit.tab'], ['id' => $id, 'tab' => 'access']) }}">{{ __('Access') }}</a>
        @else
            {{ __('Access') }}
        @endif
    </li>
    <li class="tabmenu-item roles-button {{ $active === 'roles' ? 'active' : '' }}">
        @if ($id)
            <a href="{{ route($routeNames['edit.tab'], ['id' => $id, 'tab' => 'roles']) }}">{{ __('Roles') }}</a>
        @else
            {{ __('Roles') }}
        @endif
    </li>
    <li class="tabmenu-item two-factor-button {{ $active === 'two-factor' ? 'active' : '' }}">
        @if ($id)
            <a href="{{ route($routeNames['edit.tab'], ['id' => $id, 'tab' => 'two-factor']) }}">{{ __('2FA') }}</a>
        @else
            {{ __('2FA') }}
        @endif
    </li>
    <li class="tabmenu-item image-button {{ $active === 'image' ? 'active' : '' }}">
        @if ($id)
            <a href="{{ route($routeNames['edit.tab'], ['id' => $id, 'tab' => 'image']) }}">{{ __('Image') }}</a>
        @else
            {{ __('Image') }}
        @endif
    </li>
</ul>
