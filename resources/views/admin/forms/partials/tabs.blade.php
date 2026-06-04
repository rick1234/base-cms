@php
    $formId = $form->id;
    $tabs = [
        'edit' => ['label' => __('Algemeen'), 'route' => $routeNames['edit']],
        'builder' => ['label' => __('Formulier'), 'route' => $routeNames['edit.tab'], 'parameters' => ['tab' => 'builder']],
        'recipients' => ['label' => __('Ontvangers'), 'route' => $routeNames['edit.tab'], 'parameters' => ['tab' => 'recipients']],
        'response' => ['label' => __('Bevestigingsmail'), 'route' => $routeNames['edit.tab'], 'parameters' => ['tab' => 'response']],
        'submissions' => ['label' => __('Ontvangen berichten'), 'route' => $routeNames['submissions']],
    ];
@endphp

@if ($formId)
    <div class="item-tabs-container">
        @foreach ($tabs as $tab => $tabData)
            <a class="{{ ($activeTab ?? 'edit') === $tab ? 'active' : '' }}" href="{{ route($tabData['route'], array_merge(['id' => $formId], $tabData['parameters'] ?? [])) }}">
                {{ $tabData['label'] }}
            </a>
        @endforeach
    </div>
@endif
