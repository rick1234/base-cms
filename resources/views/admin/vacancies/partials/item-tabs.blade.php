@php
    $id = $vacancy?->id;
    $tabs = [
        'info' => __('Algemeen'),
        'form' => __('Formulier'),
        'seo' => __('SEO'),
    ];
@endphp

<div class="item-tabs-container">
    @foreach ($tabs as $tab => $label)
        @if ($id)
            <a class="{{ $active === $tab ? 'active' : '' }}" href="{{ $tab === 'info' ? route($routeNames['edit'], ['id' => $id]) : route($routeNames['edit.tab'], ['id' => $id, 'tab' => $tab]) }}">{{ $label }}</a>
        @else
            <span class="{{ $active === $tab ? 'active' : '' }}">{{ $label }}</span>
        @endif
    @endforeach
</div>
