@php
    $productId = $product->id;
    $tabs = [
        'edit' => ['label' => __('Product'), 'route' => $routeNames['edit']],
        'images' => ['label' => __('Afbeeldingen'), 'route' => $routeNames['images']],
        'seo' => ['label' => __('SEO'), 'route' => $routeNames['edit.tab'], 'parameters' => ['tab' => 'seo']],
        'options' => ['label' => __('Opties'), 'route' => $routeNames['options']],
        'translations' => ['label' => __('Vertalingen'), 'route' => $routeNames['translations']],
        'videos' => ['label' => __('Video'), 'route' => $routeNames['videos']],
        'combinations' => ['label' => __('Combinaties'), 'route' => $routeNames['combinations']],
    ];
@endphp

@if ($productId)
    <div class="item-tabs-container">
        @foreach ($tabs as $tab => $tabData)
            <a class="{{ ($activeTab ?? 'edit') === $tab ? 'active' : '' }}" href="{{ route($tabData['route'], ['id' => $productId, ...($tabData['parameters'] ?? [])]) }}">
                {{ $tabData['label'] }}
            </a>
        @endforeach
    </div>
@endif
