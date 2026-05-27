@php
    $faqItemId = $faqItem->id;
    $tabs = [
        'edit' => ['label' => __('Basis informatie'), 'route' => $routeNames['edit']],
        'images' => ['label' => __('Fotoalbum'), 'route' => $routeNames['images']],
        'videos' => ['label' => __("Video's"), 'route' => $routeNames['videos']],
    ];
@endphp

@if ($faqItemId)
    <div class="item-tabs-container">
        @foreach ($tabs as $tab => $tabData)
            <a class="{{ ($activeTab ?? 'edit') === $tab ? 'active' : '' }}" href="{{ route($tabData['route'], ['id' => $faqItemId]) }}">
                {{ $tabData['label'] }}
            </a>
        @endforeach
    </div>
@endif
