@php
    $id = $download?->id;
    $tabs = [
        'general' => __('Algemeen'),
        'invites' => __('E-mail uitnodigingen'),
        'log' => __('Downloadlog'),
        'qr' => __('QR-code'),
    ];

    if (auth()->user()?->can('access-superuser')) {
        $tabs = [
            'general' => __('Algemeen'),
            'storage' => __('Bestandsbeveiliging'),
            'invites' => __('E-mail uitnodigingen'),
            'log' => __('Downloadlog'),
            'qr' => __('QR-code'),
        ];
    }
@endphp

<ul class="tabmenu">
    @foreach ($tabs as $tab => $label)
        <li class="tabmenu-item {{ $tab }}-button {{ $active === $tab ? 'active' : '' }}">
            @if ($id)
                <a href="{{ $tab === 'general' ? route($routeNames['edit'], ['id' => $id]) : route($routeNames['edit.tab'], ['id' => $id, 'tab' => $tab]) }}">{{ $label }}</a>
            @else
                {{ $label }}
            @endif
        </li>
    @endforeach
</ul>
