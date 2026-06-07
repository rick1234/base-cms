@extends('layouts.frontend')

@php
    $routeLocale = request()->route('locale');
    $formsIndexRoute = $routeLocale
        ? route('frontend.locale.forms.index', ['locale' => $routeLocale])
        : route('frontend.forms.index');
    $hasHero = filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN);
    $structuredData = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'ContactPage',
        'name' => $form->name,
        'description' => $form->description,
        'url' => url()->current(),
    ]);
@endphp

@section('content')
    <article class="form-detail-page">
        @if ($hasHero)
            <header class="page-hero eyecatcher-container">
                <div class="eyecatcher-item">
                    <div class="eyecatcher-caption">
                        <p class="form-hero-kicker">{{ __('Form') }}</p>
                        <h1 class="page-hero-title eyecatcher-caption-title">{{ $form->name }}</h1>
                        @if ($form->description)
                            <p class="page-hero-intro eyecatcher-caption-text">{{ $form->description }}</p>
                        @endif
                    </div>
                </div>
            </header>
        @endif

        <section class="breadcrumbs-container" aria-label="{{ __('Breadcrumbs') }}">
            <div class="wrapper-container">
                <ol class="breadcrumb-list">
                    <li><a href="{{ route('frontend.home') }}">{{ __('Home') }}</a></li>
                    <li><a href="{{ $formsIndexRoute }}">{{ __('Forms') }}</a></li>
                    <li aria-current="page">{{ $form->name }}</li>
                </ol>
            </div>
        </section>

        <section class="page-content form-detail">
            <div class="wrapper-container form-detail-layout">
                <div class="form-detail-main content-stack">
                    @unless ($hasHero)
                        <p class="form-hero-kicker">{{ __('Form') }}</p>
                        <h1 class="title">{{ $form->name }}</h1>
                        @if ($form->description)
                            <p class="page-content-intro">{{ $form->description }}</p>
                        @endif
                    @endunless

                    @include('frontend.forms.render', ['form' => $form])
                </div>

                @if ($form->categories->isNotEmpty())
                    <aside class="form-detail-aside" aria-label="{{ __('Form details') }}">
                        <dl class="form-meta-list">
                            <div>
                                <dt>{{ __('Categories') }}</dt>
                                <dd>
                                    <ul class="form-chip-list">
                                        @foreach ($form->categories as $category)
                                            <li>{{ $category->name }}</li>
                                        @endforeach
                                    </ul>
                                </dd>
                            </div>
                        </dl>

                        <a class="form-back-link" href="{{ $formsIndexRoute }}">{{ __('Back to forms') }}</a>
                    </aside>
                @endif
            </div>
        </section>

        <script type="application/ld+json">{!! json_encode($structuredData, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    </article>
@endsection
