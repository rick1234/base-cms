@php
    $pages = collect($frontendNavigationPages ?? []);
    $navigationItems = collect($frontendNavigationItems ?? []);

    if ($navigationItems->isEmpty()) {
        $homePage = $pages->firstWhere('slug', 'home');
        $fallbackItems = [[
            'title' => $homePage?->navigation_label ?? $homePage?->title ?? __('Home'),
            'url' => route('frontend.home'),
            'target_blank' => false,
            'external' => false,
            'children' => [],
        ]];

        $fallbackItems = array_merge($fallbackItems, $pages
            ->reject(fn ($page): bool => $page->slug === 'home')
            ->map(fn ($page): array => [
                'title' => $page->navigation_label ?: $page->title,
                'url' => route('frontend.pages.show', ['slug' => $page->slug]),
                'target_blank' => false,
                'external' => false,
                'children' => [],
            ])
            ->values()
            ->all());

        $navigationItems = collect($fallbackItems);
    }
@endphp

@if ($navigationItems->isNotEmpty())
    <nav class="navigation-container" aria-label="{{ __('Primary navigation') }}">
        <div class="wrapper-container">
            <ul class="navigation-list">
                @include('frontend.partials.navigation.items', ['items' => $navigationItems])
            </ul>
        </div>
    </nav>
@endif
