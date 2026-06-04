@php
    $locationId = $location->id;
    $tabs = [
        'general' => ['label' => __('Algemeen'), 'route' => $routeNames['edit']],
        'location' => ['label' => __('Locatie'), 'route' => $routeNames['edit.tab']],
        'images' => ['label' => __('Fotoalbum'), 'route' => $routeNames['images']],
        'opening-hours' => ['label' => __('Openingstijden'), 'route' => $routeNames['opening-hours']],
    ];
@endphp

@if ($locationId)
    <div class="item-tabs-container">
        @foreach ($tabs as $tab => $tabData)
            <a class="{{ ($activeTab ?? 'general') === $tab ? 'active' : '' }}" href="{{ $tab === 'location' ? route($tabData['route'], ['id' => $locationId, 'tab' => $tab]) : route($tabData['route'], ['id' => $locationId]) }}">
                {{ $tabData['label'] }}
            </a>
        @endforeach
    </div>
@endif
