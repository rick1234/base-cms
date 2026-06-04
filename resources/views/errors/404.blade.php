@php
    $page = [
        'meta_title' => __('Page not found'),
        'meta_description' => __('The page you are looking for could not be found. Use the navigation or search to continue.'),
    ];
    $robots = 'noindex, follow';
@endphp

@extends('layouts.frontend')

@section('content')
    <article class="not-found-page">
        <header class="page-hero eyecatcher-container not-found-hero">
            <div class="eyecatcher-item">
                <div class="eyecatcher-caption">
                    <p class="not-found-status">{{ __('Error 404') }}</p>
                    <h1 class="page-hero-title eyecatcher-caption-title">{{ __('Page not found') }}</h1>
                    <p class="page-hero-intro eyecatcher-caption-text">{{ __('This address may have moved, been renamed, or no longer exists.') }}</p>
                </div>
            </div>
        </header>

        <section class="breadcrumbs-container" aria-label="{{ __('Breadcrumbs') }}">
            <div class="wrapper-container">
                <ol class="breadcrumb-list">
                    <li><a href="{{ route('frontend.home') }}">{{ __('Home') }}</a></li>
                    <li aria-current="page">{{ __('Page not found') }}</li>
                </ol>
            </div>
        </section>

        <section class="page-content not-found-content">
            <div class="wrapper-container not-found-layout">
                <div class="not-found-copy content-stack">
                    <h2 class="title">{{ __('Let us get you back on track') }}</h2>
                    <p class="page-content-body">{{ __('Use the menu, go back to the homepage, or search for the page you need.') }}</p>

                    <div class="not-found-actions">
                        <a class="page-block-button not-found-primary-action" href="{{ route('frontend.home') }}">
                            <span class="site-material-icon mso" aria-hidden="true">home</span>
                            {{ __('Go to homepage') }}
                        </a>
                        <a class="not-found-secondary-action" href="{{ route('frontend.search') }}">
                            <span class="site-material-icon mso" aria-hidden="true">search</span>
                            {{ __('Open search page') }}
                        </a>
                    </div>
                </div>

                <aside class="not-found-search" aria-labelledby="not-found-search-title">
                    <h2 id="not-found-search-title" class="title">{{ __('Search this website') }}</h2>
                    <p>{{ __('A keyword, product name, or page title usually gets you moving again.') }}</p>
                    @include('frontend.partials.search-form')
                </aside>
            </div>
        </section>
    </article>
@endsection
