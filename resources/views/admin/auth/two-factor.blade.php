@extends('layouts.admin')

@section('title', __('Authenticator verification'))

@section('body')
    <main class="admin-login">
        <section class="admin-login-panel content-stack" aria-labelledby="admin-two-factor-title">
            <h1 id="admin-two-factor-title">{{ __('Authenticator verification') }}</h1>
            <p>{{ __('Enter the code from your authenticator app to finish signing in.') }}</p>

            <form class="form-stack" method="post" action="{{ route('admin.login.two-factor.store') }}">
                @csrf

                <div class="field">
                    <label class="field-label" for="two_factor_code">{{ __('Authenticator code') }}</label>
                    <input class="field-input" id="two_factor_code" name="two_factor_code" type="text" inputmode="numeric" autocomplete="one-time-code" value="{{ old('two_factor_code') }}" required autofocus>
                    @error('two_factor_code')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-actions">
                    <button class="button" type="submit">{{ __('Verify') }}</button>
                    <a class="button button-secondary" href="{{ route('admin.login') }}">{{ __('Back to login') }}</a>
                </div>
            </form>
        </section>
    </main>
@endsection
