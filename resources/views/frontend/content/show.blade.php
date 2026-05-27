@extends('layouts.frontend')

@section('content')
    <article class="content-item-page">
        <header class="page-hero">
            <div class="site-container content-stack">
                <h1 class="page-hero-title">{{ $contentItem->title }}</h1>
                @if ($contentItem->subtitle)
                    <p class="page-hero-intro">{{ $contentItem->subtitle }}</p>
                @elseif ($contentItem->intro)
                    <p class="page-hero-intro">{{ $contentItem->intro }}</p>
                @endif
            </div>
        </header>

        <section class="page-content">
            <div class="site-container content-stack">
                @if ($contentItem->intro && $contentItem->subtitle)
                    <p class="page-content-intro">{{ $contentItem->intro }}</p>
                @endif

                @if ($contentItem->body)
                    <div class="page-content-body">{{ $contentItem->body }}</div>
                @endif

                <x-page-blocks.renderer :blocks="$contentItem->structured_blocks" />
            </div>
        </section>
    </article>
@endsection
