<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', __('Admin')) | {{ config('app.name', 'Base CMS') }}</title>
        <meta name="robots" content="noindex,nofollow">
        @vite(['resources/scss/app.scss', 'resources/js/app.js'])
        @filamentStyles
        @livewireStyles
    </head>
    <body>
        <div class="admin-shell">
            @include('flash::message')
            @yield('body')
        </div>
        @livewireScripts
        @filamentScripts(withCore: true)
    </body>
</html>
