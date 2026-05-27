@extends('layouts.frontend')

@section('content')
    <article>
        @if (filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN))
            <header class="page-hero eyecatcher-container">
                <div class="eyecatcher-item">
                    <div class="eyecatcher-caption">
                        <h1 class="page-hero-title eyecatcher-caption-title">{{ $page->title }}</h1>
                        @if ($page->excerpt)
                            <p class="page-hero-intro eyecatcher-caption-text">{{ $page->excerpt }}</p>
                        @endif
                    </div>
                </div>
            </header>
        @endif

        <section class="breadcrumbs-container" aria-label="{{ __('Breadcrumbs') }}">
            <div class="wrapper-container">
                <ol class="breadcrumb-list">
                    <li><a href="{{ route('frontend.home') }}">{{ __('Home') }}</a></li>
                    <li aria-current="page">{{ $page->navigation_label ?: $page->title }}</li>
                </ol>
            </div>
        </section>

        <section class="page-content">
            <div class="wrapper-container content-stack">
                @unless (filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN))
                    <h1 class="title">{{ $page->title }}</h1>
                    @if ($page->excerpt)
                        <p class="page-content-intro">{{ $page->excerpt }}</p>
                    @endif
                @endunless
                <div class="page-content-body">{{ $page->body }}</div>
            </div>
        </section>
    </article>
@endsection
