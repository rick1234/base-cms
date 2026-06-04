@php
    $id = $banner?->id;
    $tabs = [
        'general' => __('Algemeen'),
        'image' => __('Afbeelding'),
        'translations' => __('Vertalingen'),
    ];
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
