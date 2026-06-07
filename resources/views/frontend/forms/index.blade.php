@extends('layouts.frontend')

@php
    $routeLocale = request()->route('locale');
    $formsIndexRoute = $routeLocale
        ? route('frontend.locale.forms.index', ['locale' => $routeLocale])
        : route('frontend.forms.index');
    $formRoute = fn ($form) => $routeLocale
        ? route('frontend.locale.forms.show', ['locale' => $routeLocale, 'form' => $form->slug])
        : route('frontend.forms.show', ['form' => $form->slug]);
@endphp

@section('content')
    <article class="forms-page">
        @if (filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN))
            <header class="page-hero eyecatcher-container">
                <div class="eyecatcher-item">
                    <div class="eyecatcher-caption">
                        <h1 class="page-hero-title eyecatcher-caption-title">{{ __('Forms') }}</h1>
                        <p class="page-hero-intro eyecatcher-caption-text">{{ __('Find the right form for your request.') }}</p>
                    </div>
                </div>
            </header>
        @endif

        <section class="breadcrumbs-container" aria-label="{{ __('Breadcrumbs') }}">
            <div class="wrapper-container">
                <ol class="breadcrumb-list">
                    <li><a href="{{ route('frontend.home') }}">{{ __('Home') }}</a></li>
                    <li aria-current="page">{{ __('Forms') }}</li>
                </ol>
            </div>
        </section>

        <section class="page-content forms-overview">
            <div class="wrapper-container content-stack">
                @unless (filter_var(data_get($domainTemplateSettings ?? [], 'show_hero', true), FILTER_VALIDATE_BOOLEAN))
                    <h1 class="title">{{ __('Forms') }}</h1>
                    <p class="page-content-intro">{{ __('Find the right form for your request.') }}</p>
                @endunless

                @if ($categories->isNotEmpty())
                    <nav class="form-category-nav" aria-label="{{ __('Form categories') }}">
                        <a @class(['form-category-link', 'is-active' => $activeCategory === null]) href="{{ $formsIndexRoute }}">
                            {{ __('All forms') }}
                        </a>
                        @foreach ($categories as $category)
                            <a @class(['form-category-link', 'is-active' => $activeCategory?->is($category)]) href="{{ $formsIndexRoute }}?category={{ urlencode($category->slug) }}">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                @if ($forms->isEmpty())
                    <p class="forms-empty-state">{{ __('No forms found.') }}</p>
                @else
                    <div class="form-card-grid">
                        @foreach ($forms as $form)
                            <article class="form-card">
                                <div class="form-card-content">
                                    <h2 class="form-card-title">
                                        <a href="{{ $formRoute($form) }}">{{ $form->name }}</a>
                                    </h2>

                                    @if ($form->description)
                                        <p>{{ $form->description }}</p>
                                    @endif

                                    @if ($form->categories->isNotEmpty())
                                        <ul class="form-chip-list" aria-label="{{ __('Categories') }}">
                                            @foreach ($form->categories as $category)
                                                <li>{{ $category->name }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                                <a class="form-card-action" href="{{ $formRoute($form) }}">{{ __('Open form') }}</a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </article>
@endsection
