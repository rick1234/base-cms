@props([
    'template',
    'selectedSection' => null,
    'compact' => false,
])

@php
    $sections = collect($template->wireframeSections());
    $heroSection = $sections->first(fn (array $section): bool => str_contains((string) $section['handle'], 'hero'));
    $mainSections = $sections
        ->reject(fn (array $section): bool => ($heroSection['handle'] ?? null) === ($section['handle'] ?? null))
        ->values();
@endphp

<section class="template-wireframe {{ $compact ? 'is-compact' : '' }}" aria-label="{{ __('Template wireframe preview') }}">
    <header class="template-wireframe-header">
        <div>
            <h2>{{ $template->name ?: __('Template preview') }}</h2>
            <span>{{ __('Wireframe preview') }}</span>
        </div>
        <span class="template-wireframe-handle">{{ $template->handle ?: __('New template') }}</span>
    </header>

    <div class="template-wireframe-canvas">
        <div class="template-wireframe-browser">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="template-wireframe-site-header">
            <span class="template-wireframe-logo"></span>
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="template-wireframe-hero {{ $selectedSection && ($heroSection['handle'] ?? null) === $selectedSection ? 'is-selected' : '' }}">
            <span>{{ $heroSection['label'] ?? __('Hero') }}</span>
        </div>

        <div class="template-wireframe-main">
            <div class="template-wireframe-content">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <div class="template-wireframe-section-list">
                @forelse ($mainSections as $section)
                    <span class="template-wireframe-section {{ $selectedSection === $section['handle'] ? 'is-selected' : '' }}">
                        <span class="template-wireframe-section-dot" aria-hidden="true"></span>
                        <span>{{ $section['label'] ?: $section['handle'] }}</span>
                    </span>
                @empty
                    <span class="template-wireframe-section is-empty">
                        <span class="template-wireframe-section-dot" aria-hidden="true"></span>
                        <span>{{ __('No defined sections') }}</span>
                    </span>
                @endforelse
            </div>
        </div>

        <div class="template-wireframe-footer">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</section>
