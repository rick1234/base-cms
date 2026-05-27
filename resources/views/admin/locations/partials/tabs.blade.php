@php
    $locationId = $location->id;
    $tabs = [
        'edit' => ['label' => __('Basis informatie'), 'route' => $routeNames['edit']],
        'images' => ['label' => __('Fotoalbum'), 'route' => $routeNames['images']],
        'opening-hours' => ['label' => __('Openingstijden'), 'route' => $routeNames['opening-hours']],
    ];
@endphp

@if ($locationId)
    <div class="item-tabs-container">
        @foreach ($tabs as $tab => $tabData)
            <a class="{{ ($activeTab ?? 'edit') === $tab ? 'active' : '' }}" href="{{ route($tabData['route'], ['id' => $locationId]) }}">
                {{ $tabData['label'] }}
            </a>
        @endforeach
    </div>
@endif
