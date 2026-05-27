@extends('layouts.frontend')

@section('content')
    @if (filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN))
        <section class="page-hero eyecatcher-container large">
            <div class="eyecatcher-item">
                <div class="eyecatcher-caption">
                    <h1 class="page-hero-title eyecatcher-caption-title">{{ $page?->title ?? __('Base CMS') }}</h1>
                    <p class="page-hero-intro eyecatcher-caption-text">{{ $page?->excerpt ?? __('A strict Laravel base for custom websites.') }}</p>
                </div>
            </div>
        </section>
    @endif

    <section class="page-content homepage-widget homepage-widget-intro">
        <div class="wrapper-container content-stack">
            @unless (filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN))
                <h1 class="page-hero-title">{{ $page?->title ?? __('Base CMS') }}</h1>
                <p class="page-hero-intro">{{ $page?->excerpt ?? __('A strict Laravel base for custom websites.') }}</p>
            @endunless
            <h2>{{ __('Custom websites, cleanly built') }}</h2>
            <p class="page-content-body">{{ $page?->body ?? __('Create content in the admin area and expose it through Blade or the versioned API.') }}</p>
        </div>
    </section>
@endsection
