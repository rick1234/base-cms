@php
    $location = $location ?? 'header_top';
    $sets = $activeTemplate?->uspSetsForLocation($location) ?? [];
    $showUspBar = filter_var(data_get($domainTemplateSettings ?? [], 'show_usp_bar', true), FILTER_VALIDATE_BOOLEAN);

    if ($sets === [] && $location === 'header_top') {
        $rawItems = data_get($domainTemplateSettings ?? [], 'usp_items', []);
        $items = is_array($rawItems)
            ? $rawItems
            : preg_split('/\r\n|\r|\n/', (string) $rawItems);
        $legacyItems = collect($items)
            ->map(fn (mixed $item): array => [
                'label' => trim((string) $item),
                'icon' => 'done',
            ])
            ->filter(fn (array $item): bool => $item['label'] !== '')
            ->values()
            ->all();

        if ($legacyItems !== []) {
            $sets = [
                [
                    'name' => __('Highlights'),
                    'location' => 'header_top',
                    'items' => $legacyItems,
                ],
            ];
        }
    }
@endphp

@if ($showUspBar && $sets !== [])
    @foreach ($sets as $set)
        <section class="usp-container usp-container-{{ str_replace('_', '-', $location) }}" aria-label="{{ $set['name'] ?: __('Highlights') }}">
            <div class="wrapper-container">
                <ul class="usp-listing">
                    @foreach ($set['items'] as $item)
                        <li class="usp-listing-item">
                            <span class="site-material-icon mso" aria-hidden="true">{{ $item['icon'] ?: 'done' }}</span>
                            <span>{{ $item['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endforeach
@endif
