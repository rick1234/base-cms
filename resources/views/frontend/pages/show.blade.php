@extends('layouts.frontend')

@section('content')
    <article>
        <header class="page-hero">
            <div class="site-container content-stack">
                <h1 class="page-hero__title">{{ $page->title }}</h1>
                @if ($page->excerpt)
                    <p class="page-hero__intro">{{ $page->excerpt }}</p>
                @endif
            </div>
        </header>

        <section class="page-content">
            <div class="site-container">
                <div class="page-content__body">{{ $page->body }}</div>
            </div>
        </section>
    </article>
@endsection
