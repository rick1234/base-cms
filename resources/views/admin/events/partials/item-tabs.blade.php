@php
    $id = $event?->id;
    $tabs = [
        'general' => __('Algemeen'),
        'schedule' => __('Tijdschema'),
        'form' => __('Formulier'),
        'attachments' => __('Bijlagen'),
        'images' => __('Fotoalbum'),
        'seo' => __('SEO'),
    ];
@endphp

<div class="item-tabs-container">
    @foreach ($tabs as $tab => $label)
        @if ($id)
            <a class="{{ $active === $tab ? 'active' : '' }}" href="{{ $tab === 'general' ? route($routeNames['edit'], ['id' => $id]) : route($routeNames['edit.tab'], ['id' => $id, 'tab' => $tab]) }}">{{ $label }}</a>
        @else
            <span class="{{ $active === $tab ? 'active' : '' }}">{{ $label }}</span>
        @endif
    @endforeach
</div>
