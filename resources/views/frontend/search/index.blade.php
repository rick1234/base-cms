@extends('layouts.frontend')

@section('content')
    <section class="page-content search-page">
        <div class="wrapper-container content-stack">
            <h1 class="title">{{ __('Search') }}</h1>

            @include('frontend.partials.search-form')

            @if ($query === '')
                <p>{{ __('Enter a search term to search the website.') }}</p>
            @else
                <p>{{ __('Search results for ":query"', ['query' => $query]) }}</p>

                @if ($pageResults->isEmpty() && $contentResults->isEmpty())
                    <p>{{ __('No results found.') }}</p>
                @else
                    <div class="search-results">
                        @foreach ($pageResults as $result)
                            <article class="search-result">
                                <h2 class="sub-title">
                                    <a href="{{ $result->slug === 'home' ? route('frontend.home') : route('frontend.pages.show', ['slug' => $result->slug]) }}">
                                        {{ $result->title }}
                                    </a>
                                </h2>
                                @if ($result->excerpt)
                                    <p>{{ $result->excerpt }}</p>
                                @endif
                            </article>
                        @endforeach

                        @foreach ($contentResults as $result)
                            <article class="search-result">
                                <h2 class="sub-title">
                                    <a href="{{ route('frontend.pages.show', ['slug' => $result->slug]) }}">
                                        {{ $result->title }}
                                    </a>
                                </h2>
                                @if ($result->intro)
                                    <p>{{ $result->intro }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </section>
@endsection
