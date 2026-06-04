@extends('layouts.frontend')

@section('content')
    <article class="content-item-page">
        @if (filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN))
            <header class="page-hero eyecatcher-container">
                <div class="eyecatcher-item">
                    <div class="eyecatcher-caption">
                        <h1 class="page-hero-title eyecatcher-caption-title">{{ $contentItem->title }}</h1>
                        @if ($contentItem->subtitle)
                            <p class="page-hero-intro eyecatcher-caption-text">{{ $contentItem->subtitle }}</p>
                        @endif
                    </div>
                </div>
            </header>
        @endif

        <section class="breadcrumbs-container" aria-label="{{ __('Breadcrumbs') }}">
            <div class="wrapper-container">
                <ol class="breadcrumb-list">
                    <li><a href="{{ route('frontend.home') }}">{{ __('Home') }}</a></li>
                    <li aria-current="page">{{ $contentItem->title }}</li>
                </ol>
            </div>
        </section>

        <section class="page-content">
            <div class="wrapper-container content-stack">
                @unless (filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN))
                    <h1 class="title">{{ $contentItem->title }}</h1>
                @endunless

                <x-page-blocks.renderer :blocks="$contentItem->structured_blocks" />
            </div>
        </section>
    </article>
@endsection
