@extends('layouts.frontend')

@php
    use App\Support\Media\HandledImage;

    $routeLocale = request()->route('locale');
    $eventsIndexRoute = $routeLocale
        ? route('frontend.locale.events.index', ['locale' => $routeLocale])
        : route('frontend.events.index');
    $assetUrl = function (?string $path): ?string {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return preg_match('/^https?:\/\//', $path) === 1 ? $path : asset(ltrim($path, '/'));
    };
    $primaryImage = $event->images->first();
    $primaryImageHandle = $primaryImage?->image ?? ($event->image_path ? $event->image : null);
    $primaryImageUrl = $primaryImageHandle instanceof HandledImage
        ? $primaryImageHandle->handle(1200, null, false)
        : $assetUrl($primaryImage?->image_path ?: $event->image_path);
    $hasHero = filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN);
    $structuredData = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => $event->title,
        'description' => $event->meta_description ?: $event->intro,
        'startDate' => $event->starts_at?->toDateString(),
        'endDate' => $event->ends_at?->toDateString(),
        'image' => $primaryImageUrl ? [$primaryImageUrl] : null,
        'url' => url()->current(),
    ]);
@endphp

@section('content')
    <article class="event-detail-page">
        @if ($hasHero)
            <header class="page-hero eyecatcher-container">
                <div class="eyecatcher-item">
                    <div class="eyecatcher-caption">
                        <p class="event-hero-kicker">{{ __('Event') }}</p>
                        <h1 class="page-hero-title eyecatcher-caption-title">{{ $event->title }}</h1>
                        @if ($event->subtitle)
                            <p class="page-hero-intro eyecatcher-caption-text">{{ $event->subtitle }}</p>
                        @endif
                    </div>
                </div>
            </header>
        @endif

        <section class="breadcrumbs-container" aria-label="{{ __('Breadcrumbs') }}">
            <div class="wrapper-container">
                <ol class="breadcrumb-list">
                    <li><a href="{{ route('frontend.home') }}">{{ __('Home') }}</a></li>
                    <li><a href="{{ $eventsIndexRoute }}">{{ __('Events') }}</a></li>
                    <li aria-current="page">{{ $event->title }}</li>
                </ol>
            </div>
        </section>

        <section class="page-content event-detail">
            <div class="wrapper-container event-detail-layout">
                <div class="event-detail-main content-stack">
                    @unless ($hasHero)
                        <p class="event-hero-kicker">{{ __('Event') }}</p>
                        <h1 class="title">{{ $event->title }}</h1>
                        @if ($event->subtitle)
                            <p class="page-content-intro">{{ $event->subtitle }}</p>
                        @endif
                    @endunless

                    @if ($primaryImageUrl)
                        <figure class="event-detail-image">
                            <x-media.image :image="$primaryImageHandle" :alt="$primaryImage?->is_decorative ? '' : ($primaryImage?->alt_text ?: $event->title)" :width="1200" group="event-images" loading="eager" />
                            @if ($primaryImage?->caption)
                                <figcaption>{{ $primaryImage->caption }}</figcaption>
                            @endif
                        </figure>
                    @endif

                    @if ($event->intro)
                        <p class="event-detail-intro">{{ $event->intro }}</p>
                    @endif

                    @if ($event->body)
                        <div class="event-detail-body">{!! nl2br(e($event->body)) !!}</div>
                    @endif

                    <x-page-blocks.renderer :blocks="$event->structured_blocks" />

                    @if ($event->scheduleGroups->isNotEmpty() || $event->parts->isNotEmpty())
                        <section class="event-schedule" aria-labelledby="event-schedule-title">
                            <h2 id="event-schedule-title">{{ __('Schedule') }}</h2>

                            @forelse ($event->scheduleGroups as $group)
                                @if ($group->parts->isNotEmpty())
                                    <section class="event-schedule-group">
                                        <h3>{{ $group->name }}</h3>
                                        @include('frontend.events.partials.schedule-parts', ['parts' => $group->parts])
                                    </section>
                                @endif
                            @empty
                                @include('frontend.events.partials.schedule-parts', ['parts' => $event->parts])
                            @endforelse
                        </section>
                    @endif

                    @if ($event->images->skip(1)->isNotEmpty())
                        <section class="event-gallery" aria-labelledby="event-gallery-title">
                            <h2 id="event-gallery-title">{{ __('Images') }}</h2>
                            <div class="event-gallery-grid">
                                @foreach ($event->images->skip(1) as $image)
                                    @php $imageUrl = $image->image->handle(520, 390, true); @endphp
                                    @if ($imageUrl)
                                        <figure class="event-gallery-item">
                                            <x-media.image :image="$image->image" :alt="$image->is_decorative ? '' : ($image->alt_text ?: $event->title)" :width="520" :height="390" crop group="event-images" />
                                            @if ($image->caption)
                                                <figcaption>{{ $image->caption }}</figcaption>
                                            @endif
                                        </figure>
                                    @endif
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($event->attachments->isNotEmpty())
                        <section class="event-attachments" aria-labelledby="event-attachments-title">
                            <h2 id="event-attachments-title">{{ __('Downloads') }}</h2>
                            <ul class="event-attachment-list">
                                @foreach ($event->attachments as $attachment)
                                    <li>
                                        <a href="{{ $assetUrl($attachment->url) }}" download>
                                            {{ $attachment->name ?: __('Download') }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($event->form?->isActive())
                        <section class="event-registration" aria-labelledby="event-registration-title">
                            <h2 id="event-registration-title">{{ __('Register') }}</h2>
                            @include('frontend.forms.render', ['form' => $event->form])
                        </section>
                    @endif
                </div>

                <aside class="event-detail-aside" aria-label="{{ __('Event details') }}">
                    <dl class="event-meta-list">
                        @if ($event->starts_at)
                            <div>
                                <dt>{{ __('Date') }}</dt>
                                <dd>
                                    <time datetime="{{ $event->starts_at->toDateString() }}">
                                        {{ $event->starts_at->translatedFormat('j F Y') }}
                                    </time>
                                    @if ($event->ends_at && ! $event->ends_at->isSameDay($event->starts_at))
                                        <span>{{ __('until') }}</span>
                                        <time datetime="{{ $event->ends_at->toDateString() }}">
                                            {{ $event->ends_at->translatedFormat('j F Y') }}
                                        </time>
                                    @endif
                                </dd>
                            </div>
                        @endif

                        @if ($event->categories->isNotEmpty())
                            <div>
                                <dt>{{ __('Categories') }}</dt>
                                <dd>
                                    <ul class="event-chip-list">
                                        @foreach ($event->categories as $category)
                                            <li>{{ $category->name }}</li>
                                        @endforeach
                                    </ul>
                                </dd>
                            </div>
                        @endif
                    </dl>

                    <a class="event-back-link" href="{{ $eventsIndexRoute }}">{{ __('Back to events') }}</a>
                </aside>
            </div>
        </section>

        <script type="application/ld+json">{!! json_encode($structuredData, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    </article>
@endsection
