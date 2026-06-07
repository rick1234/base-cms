@extends('layouts.frontend')

@php
    $routeLocale = request()->route('locale');
    $vacanciesIndexRoute = $routeLocale
        ? route('frontend.locale.vacancies.index', ['locale' => $routeLocale])
        : route('frontend.vacancies.index');
    $vacancyRoute = fn ($vacancy) => $routeLocale
        ? route('frontend.locale.vacancies.show', ['locale' => $routeLocale, 'vacancy' => $vacancy->publicRouteKey()])
        : route('frontend.vacancies.show', ['vacancy' => $vacancy->publicRouteKey()]);
    $workModeLabel = fn (?string $workMode): ?string => match ($workMode) {
        'on-site' => __('On site'),
        'hybrid' => __('Hybrid'),
        'remote' => __('Remote'),
        default => null,
    };
@endphp

@section('content')
    <article class="vacancies-page">
        @if (filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN))
            <header class="page-hero eyecatcher-container">
                <div class="eyecatcher-item">
                    <div class="eyecatcher-caption">
                        <h1 class="page-hero-title eyecatcher-caption-title">{{ __('Vacancies') }}</h1>
                        <p class="page-hero-intro eyecatcher-caption-text">{{ __('Explore current vacancies.') }}</p>
                    </div>
                </div>
            </header>
        @endif

        <section class="breadcrumbs-container" aria-label="{{ __('Breadcrumbs') }}">
            <div class="wrapper-container">
                <ol class="breadcrumb-list">
                    <li><a href="{{ route('frontend.home') }}">{{ __('Home') }}</a></li>
                    <li aria-current="page">{{ __('Vacancies') }}</li>
                </ol>
            </div>
        </section>

        <section class="page-content vacancies-overview">
            <div class="wrapper-container content-stack">
                @unless (filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN))
                    <h1 class="title">{{ __('Vacancies') }}</h1>
                    <p class="page-content-intro">{{ __('Explore current vacancies.') }}</p>
                @endunless

                @if ($categories->isNotEmpty())
                    <nav class="vacancy-category-nav" aria-label="{{ __('Vacancy categories') }}">
                        <a @class(['vacancy-category-link', 'is-active' => $activeCategory === null]) href="{{ $vacanciesIndexRoute }}">
                            {{ __('All vacancies') }}
                        </a>
                        @foreach ($categories as $category)
                            <a @class(['vacancy-category-link', 'is-active' => $activeCategory?->is($category)]) href="{{ $vacanciesIndexRoute }}?category={{ urlencode($category->slug) }}">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                @if ($vacancies->isEmpty())
                    <p class="vacancies-empty-state">{{ __('No vacancies found.') }}</p>
                @else
                    <div class="vacancy-card-list">
                        @foreach ($vacancies as $vacancy)
                            @php
                                $metadata = (array) ($vacancy->metadata ?? []);
                                $metaItems = array_filter([
                                    $metadata['location'] ?? null,
                                    $metadata['employment_type'] ?? null,
                                    $metadata['hours'] ?? null,
                                    $workModeLabel($metadata['work_mode'] ?? null),
                                ]);
                            @endphp
                            <article class="vacancy-card">
                                <div class="vacancy-card-content">
                                    <h2 class="vacancy-card-title">
                                        <a href="{{ $vacancyRoute($vacancy) }}">{{ $vacancy->title }}</a>
                                    </h2>

                                    @if ($metaItems !== [])
                                        <ul class="vacancy-meta-inline" aria-label="{{ __('Vacancy details') }}">
                                            @foreach ($metaItems as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if ($vacancy->body)
                                        <p>{{ str($vacancy->body)->stripTags()->limit(180) }}</p>
                                    @endif

                                    @if ($vacancy->categories->isNotEmpty())
                                        <ul class="vacancy-chip-list" aria-label="{{ __('Categories') }}">
                                            @foreach ($vacancy->categories as $category)
                                                <li>{{ $category->name }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                                <a class="vacancy-card-action" href="{{ $vacancyRoute($vacancy) }}">{{ __('View vacancy') }}</a>
                            </article>
                        @endforeach
                    </div>

                    @if ($vacancies->hasPages())
                        <nav class="vacancy-pagination" aria-label="{{ __('Pagination') }}">
                            @if ($vacancies->onFirstPage())
                                <span aria-disabled="true">{{ __('Previous') }}</span>
                            @else
                                <a href="{{ $vacancies->previousPageUrl() }}" rel="prev">{{ __('Previous') }}</a>
                            @endif

                            <span>{{ __('Page :current of :last', ['current' => $vacancies->currentPage(), 'last' => $vacancies->lastPage()]) }}</span>

                            @if ($vacancies->hasMorePages())
                                <a href="{{ $vacancies->nextPageUrl() }}" rel="next">{{ __('Next') }}</a>
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
