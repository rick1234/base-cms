@extends('layouts.admin')

@section('title', __('Admin login'))

@section('body')
    <main class="admin-login">
        <section class="admin-login-panel content-stack" aria-labelledby="admin-login-title">
            <h1 id="admin-login-title">{{ __('Admin login') }}</h1>

            <form class="form-stack" method="post" action="{{ route('admin.login.store') }}">
                @csrf

                <div class="field">
                    <label class="field-label" for="email">{{ __('Email') }}</label>
                    <input class="field-input" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
                    @error('email')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label class="field-label" for="password">{{ __('Password') }}</label>
                    <input class="field-input" id="password" name="password" type="password" required autocomplete="current-password">
                    @error('password')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <label>
                    <input name="remember" type="checkbox" value="1">
                    {{ __('Remember me') }}
                </label>

                <div class="form-actions">
                    <button class="button" type="submit">{{ __('Log in') }}</button>
                </div>
            </form>
        </section>
    </main>
@endsection
