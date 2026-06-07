@extends('layouts.frontend')

@php
    $routeLocale = request()->route('locale');
    $eventsIndexRoute = $routeLocale
        ? route('frontend.locale.events.index', ['locale' => $routeLocale])
        : route('frontend.events.index');
    $eventRoute = fn ($event) => $routeLocale
        ? route('frontend.locale.events.show', ['locale' => $routeLocale, 'event' => $event->slug])
        : route('frontend.events.show', ['event' => $event->slug]);
    $assetUrl = function (?string $path): ?string {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return preg_match('/^https?:\/\//', $path) === 1 ? $path : asset(ltrim($path, '/'));
    };
@endphp

@section('content')
    <article class="events-page">
        @if (filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN))
            <header class="page-hero eyecatcher-container">
                <div class="eyecatcher-item">
                    <div class="eyecatcher-caption">
                        <h1 class="page-hero-title eyecatcher-caption-title">{{ __('Events') }}</h1>
                        <p class="page-hero-intro eyecatcher-caption-text">{{ __('Discover upcoming events.') }}</p>
                    </div>
                </div>
            </header>
        @endif

        <section class="breadcrumbs-container" aria-label="{{ __('Breadcrumbs') }}">
            <div class="wrapper-container">
                <ol class="breadcrumb-list">
                    <li><a href="{{ route('frontend.home') }}">{{ __('Home') }}</a></li>
                    <li aria-current="page">{{ __('Events') }}</li>
                </ol>
            </div>
        </section>

        <section class="page-content events-overview">
            <div class="wrapper-container content-stack">
                @unless (filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN))
                    <h1 class="title">{{ __('Events') }}</h1>
                    <p class="page-content-intro">{{ __('Discover upcoming events.') }}</p>
                @endunless

                @if ($categories->isNotEmpty())
                    <nav class="event-category-nav" aria-label="{{ __('Event categories') }}">
                        <a @class(['event-category-link', 'is-active' => $activeCategory === null]) href="{{ $eventsIndexRoute }}">
                            {{ __('All events') }}
                        </a>
                        @foreach ($categories as $category)
                            <a @class(['event-category-link', 'is-active' => $activeCategory?->is($category)]) href="{{ $eventsIndexRoute }}?category={{ urlencode($category->slug) }}">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                @if ($events->isEmpty())
                    <p class="events-empty-state">{{ __('No events found.') }}</p>
                @else
                    <div class="event-card-grid">
                        @foreach ($events as $event)
                            @php
                                $primaryImage = $event->images->first();
                                $imageUrl = $assetUrl($primaryImage?->image_path ?: $event->image_path);
                            @endphp
                            <article class="event-card">
                                @if ($imageUrl)
                                    <a class="event-card-media" href="{{ $eventRoute($event) }}" aria-label="{{ __('View :title', ['title' => $event->title]) }}">
                                        <img src="{{ $imageUrl }}" alt="{{ $primaryImage?->is_decorative ? '' : ($primaryImage?->alt_text ?: $event->title) }}">
                                    </a>
                                @endif

                                <div class="event-card-content">
                                    @if ($event->starts_at)
                                        <time class="event-date" datetime="{{ $event->starts_at->toDateString() }}">
                                            {{ $event->starts_at->translatedFormat('j F Y') }}
                                        </time>
                                    @endif

                                    <h2 class="event-card-title">
                                        <a href="{{ $eventRoute($event) }}">{{ $event->title }}</a>
                                    </h2>

                                    @if ($event->subtitle)
                                        <p class="event-card-subtitle">{{ $event->subtitle }}</p>
                                    @elseif ($event->intro)
                                        <p>{{ $event->intro }}</p>
                                    @endif

                                    @if ($event->categories->isNotEmpty())
                                        <ul class="event-chip-list" aria-label="{{ __('Categories') }}">
                                            @foreach ($event->categories as $category)
                                                <li>{{ $category->name }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if ($events->hasPages())
                        <nav class="event-pagination" aria-label="{{ __('Pagination') }}">
                            @if ($events->onFirstPage())
                                <span aria-disabled="true">{{ __('Previous') }}</span>
                            @else
                                <a href="{{ $events->previousPageUrl() }}" rel="prev">{{ __('Previous') }}</a>
                            @endif

                            <span>{{ __('Page :current of :last', ['current' => $events->currentPage(), 'last' => $events->lastPage()]) }}</span>

                            @if ($events->hasMorePages())
                                <a href="{{ $events->nextPageUrl() }}" rel="next">{{ __('Next') }}</a>
                            @else
                                <span aria-disabled="true">{{ __('Next') }}</span>
                            @endif
                        </nav>
                    @endif
                @endif
            </div>
        </section>
    </article>
@endsection
