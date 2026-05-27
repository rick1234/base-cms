<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-seo.meta :page="$page ?? null" :robots="$robots ?? null" />
        @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div class="site-shell">
            @include('flash::message')

            <header class="site-header">
                <div class="site-container site-header-inner">
                    <a class="site-brand" href="{{ route('frontend.home') }}">{{ config('app.name', 'Base CMS') }}</a>
                    <nav class="site-navigation" aria-label="{{ __('Primary navigation') }}">
                        <ul class="site-navigation-list">
                            <li><a class="site-navigation-link" href="{{ route('frontend.home') }}">{{ __('Home') }}</a></li>
                        </ul>
                    </nav>
                    @if (($enabledLocales ?? collect())->count() > 1)
                        <div class="site-language-switcher" aria-label="{{ __('Language') }}">
                            @foreach ($enabledLocales as $language)
                                <form method="post" action="{{ route('locale.update', ['locale' => $language->code]) }}">
                                    @csrf
                                    <button class="site-language-button {{ $currentLocale === $language->code ? 'is-active' : '' }}" type="submit" @disabled($currentLocale === $language->code)>
                                        {{ strtoupper($language->code) }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                </div>
            </header>

            <main class="site-main">
                @yield('content')
            </main>

            <footer class="site-footer">
                <div class="site-container">
                    <p>{{ __('Reusable Laravel base for custom websites.') }}</p>
                </div>
            </footer>
        </div>
    </body>
</html>
