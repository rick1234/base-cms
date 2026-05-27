@php
    $formId = $form->id;
    $tabs = [
        'edit' => ['label' => __('Basis informatie'), 'route' => $routeNames['edit']],
        'builder' => ['label' => __('Form builder'), 'route' => $routeNames['edit'], 'query' => ['tab' => 'builder']],
        'submissions' => ['label' => __('Berichten'), 'route' => $routeNames['submissions']],
    ];
@endphp

@if ($formId)
    <div class="item-tabs-container">
        @foreach ($tabs as $tab => $tabData)
            <a class="{{ ($activeTab ?? 'edit') === $tab ? 'active' : '' }}" href="{{ route($tabData['route'], array_merge(['id' => $formId], $tabData['query'] ?? [])) }}">
                {{ $tabData['label'] }}
            </a>
        @endforeach
    </div>
@endif
