@extends('layouts.frontend')

@php
    $routeLocale = request()->route('locale');
    $vacanciesIndexRoute = $routeLocale
        ? route('frontend.locale.vacancies.index', ['locale' => $routeLocale])
        : route('frontend.vacancies.index');
    $assetUrl = function (?string $path): ?string {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return preg_match('/^https?:\/\//', $path) === 1 ? $path : asset(ltrim($path, '/'));
    };
    $metadata = (array) ($vacancy->metadata ?? []);
    $workModeLabel = match ($metadata['work_mode'] ?? null) {
        'on-site' => __('On site'),
        'hybrid' => __('Hybrid'),
        'remote' => __('Remote'),
        default => null,
    };
    $vacancyTypeLabel = match ($metadata['vacancy_type'] ?? null) {
        'volunteer' => __('Volunteer position'),
        'paid' => __('Paid position'),
        default => null,
    };
    $bodyHtml = nl2br(e(strip_tags((string) $vacancy->body)));
    $hasHero = filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN);
    $structuredData = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'JobPosting',
        'title' => $vacancy->title,
        'description' => strip_tags((string) $vacancy->body),
        'employmentType' => $metadata['employment_type'] ?? null,
        'datePosted' => $vacancy->active_from?->toDateString() ?? $vacancy->created_at?->toDateString(),
        'validThrough' => $vacancy->active_until?->endOfDay()->toIso8601String(),
        'url' => url()->current(),
        'jobLocationType' => ($metadata['work_mode'] ?? null) === 'remote' ? 'TELECOMMUTE' : null,
        'applicantLocationRequirements' => ($metadata['work_mode'] ?? null) === 'remote' ? [
            '@type' => 'Country',
            'name' => $metadata['location'] ?? __('Remote'),
        ] : null,
    ]);
@endphp

@section('content')
    <article class="vacancy-detail-page">
        @if ($hasHero)
            <header class="page-hero eyecatcher-container">
                <div class="eyecatcher-item">
                    <div class="eyecatcher-caption">
                        <p class="vacancy-hero-kicker">{{ __('Vacancy') }}</p>
                        <h1 class="page-hero-title eyecatcher-caption-title">{{ $vacancy->title }}</h1>
                        @if (($metadata['location'] ?? null) || ($metadata['employment_type'] ?? null))
                            <p class="page-hero-intro eyecatcher-caption-text">
                                {{ collect([$metadata['location'] ?? null, $metadata['employment_type'] ?? null])->filter()->implode(' · ') }}
                            </p>
                        @endif
                    </div>
                </div>
            </header>
        @endif

        <section class="breadcrumbs-container" aria-label="{{ __('Breadcrumbs') }}">
            <div class="wrapper-container">
                <ol class="breadcrumb-list">
                    <li><a href="{{ route('frontend.home') }}">{{ __('Home') }}</a></li>
                    <li><a href="{{ $vacanciesIndexRoute }}">{{ __('Vacancies') }}</a></li>
                    <li aria-current="page">{{ $vacancy->title }}</li>
                </ol>
            </div>
        </section>

        <section class="page-content vacancy-detail">
            <div class="wrapper-container vacancy-detail-layout">
                <div class="vacancy-detail-main content-stack">
                    @unless ($hasHero)
                        <p class="vacancy-hero-kicker">{{ __('Vacancy') }}</p>
                        <h1 class="title">{{ $vacancy->title }}</h1>
                    @endunless

                    @if ($bodyHtml !== '')
                        <div class="vacancy-detail-body">{!! $bodyHtml !!}</div>
                    @endif

                    @if ($vacancy->attachments->isNotEmpty())
                        <section class="vacancy-attachments" aria-labelledby="vacancy-attachments-title">
                            <h2 id="vacancy-attachments-title">{{ __('Downloads') }}</h2>
                            <ul class="vacancy-attachment-list">
                                @foreach ($vacancy->attachments as $attachment)
                                    <li>
                                        <a href="{{ $assetUrl($attachment->url) }}" download>
                                            {{ $attachment->name ?: __('Download') }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($vacancy->form?->isActive())
                        <section class="vacancy-application" aria-labelledby="vacancy-application-title">
                            <h2 id="vacancy-application-title">{{ __('Apply') }}</h2>
                            @include('frontend.forms.render', ['form' => $vacancy->form])
                        </section>
                    @endif
                </div>

                <aside class="vacancy-detail-aside" aria-label="{{ __('Vacancy details') }}">
                    <dl class="vacancy-meta-list">
                        @foreach ([
                            __('Location') => $metadata['location'] ?? null,
                            __('Vacancy type') => $vacancyTypeLabel,
                            __('Employment type') => $metadata['employment_type'] ?? null,
                            __('Work mode') => $workModeLabel,
                            __('Hours') => $metadata['hours'] ?? null,
                            __('Education level') => $metadata['education_level'] ?? null,
                            __('Experience level') => $metadata['experience_level'] ?? null,
                            __('Salary') => $metadata['salary'] ?? null,
                            __('Volunteer commitment') => $metadata['volunteer_commitment'] ?? null,
                            __('Volunteer compensation') => $metadata['volunteer_compensation'] ?? null,
                        ] as $label => $value)
                            @if ($value)
                                <div>
                                    <dt>{{ $label }}</dt>
                                    <dd>{{ $value }}</dd>
                                </div>
                            @endif
                        @endforeach

                        @if ($metadata['contact_email'] ?? null)
                            <div>
                                <dt>{{ __('Contact e-mail') }}</dt>
                                <dd><a href="mailto:{{ $metadata['contact_email'] }}">{{ $metadata['contact_email'] }}</a></dd>
                            </div>
                        @endif

                        @if ($vacancy->categories->isNotEmpty())
                            <div>
                                <dt>{{ __('Categories') }}</dt>
                                <dd>
                                    <ul class="vacancy-chip-list">
                                        @foreach ($vacancy->categories as $category)
                                            <li>{{ $category->name }}</li>
                                        @endforeach
                                    </ul>
                                </dd>
                            </div>
                        @endif
                    </dl>

                    <a class="vacancy-back-link" href="{{ $vacanciesIndexRoute }}">{{ __('Back to vacancies') }}</a>
                </aside>
            </div>
        </section>

        <script type="application/ld+json">{!! json_encode($structuredData, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    </article>
@endsection
