@php
    $rawItems = data_get($domainTemplateSettings ?? [], 'usp_items', []);
    $items = is_array($rawItems)
        ? $rawItems
        : preg_split('/\r\n|\r|\n/', (string) $rawItems);
    $items = collect($items)
        ->map(fn (mixed $item): string => trim((string) $item))
        ->filter()
        ->values();
    $showUspBar = filter_var(data_get($domainTemplateSettings ?? [], 'show_usp_bar', true), FILTER_VALIDATE_BOOLEAN);
@endphp

@if ($showUspBar && $items->isNotEmpty())
    <section class="usp-container" aria-label="{{ __('Highlights') }}">
        <div class="wrapper-container">
            <ul class="usp-listing">
                @foreach ($items as $item)
                    <li class="usp-listing-item">
                        <span class="site-material-icon admin-material-icon" aria-hidden="true">done</span>
                        <span>{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
