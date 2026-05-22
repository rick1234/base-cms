<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-seo.meta :page="$page ?? null" />
        @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div class="site-shell">
            <header class="site-header">
                <div class="site-container site-header__inner">
                    <a class="site-brand" href="{{ route('frontend.home') }}">{{ config('app.name', 'Base CMS') }}</a>
                    <nav class="site-navigation" aria-label="{{ __('Primary navigation') }}">
                        <ul class="site-navigation__list">
                            <li><a class="site-navigation__link" href="{{ route('frontend.home') }}">{{ __('Home') }}</a></li>
                        </ul>
                    </nav>
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
