@extends('layouts.frontend')

@section('content')
    <section class="page-hero">
        <div class="site-container content-stack">
            <h1 class="page-hero__title">{{ $page?->title ?? __('Base CMS') }}</h1>
            <p class="page-hero__intro">{{ $page?->excerpt ?? __('A strict Laravel base for custom websites.') }}</p>
        </div>
    </section>

    <section class="page-content">
        <div class="site-container content-stack">
            <h2>{{ __('Custom websites, cleanly built') }}</h2>
            <p class="page-content__body">{{ $page?->body ?? __('Create content in the admin area and expose it through Blade or the versioned API.') }}</p>
        </div>
    </section>
@endsection
