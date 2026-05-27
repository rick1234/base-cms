@extends('layouts.frontend')

@section('content')
    <article class="download-access">
        <header class="page-hero">
            <div class="site-container content-stack">
                <h1 class="page-hero-title">{{ $download->name }}</h1>
                @if ($download->description)
                    <p class="page-hero-intro">{{ $download->description }}</p>
                @endif
            </div>
        </header>

        <section class="download-access-section">
            <div class="site-container">
                <form class="download-access-form form-stack" method="post" action="{{ route('frontend.downloads.unlock', ['download' => $download->publicRouteKey()]) }}">
                    @csrf
                    <div class="field">
                        <label class="field-label" for="download-password">{{ __('Password') }}</label>
                        <input id="download-password" class="field-input" name="password" type="password" autocomplete="current-password" required>
                        @error('password')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-actions">
                        <button class="button button-primary" type="submit">{{ __('Download') }}</button>
                    </div>
                </form>
            </div>
        </section>
    </article>
@endsection
