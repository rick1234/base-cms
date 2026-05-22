@extends('layouts.admin')

@section('title', __('Dashboard'))

@section('body')
    <div class="admin-layout">
        @include('admin.partials.navigation')

        <main class="admin-main">
            <div class="admin-page-header">
                <div>
                    <h1>{{ __('Dashboard') }}</h1>
                    <p>{{ __('Base content, modules, and extension points.') }}</p>
                </div>
                <form method="post" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="button button--secondary" type="submit">{{ __('Log out') }}</button>
                </form>
            </div>

            <section class="admin-stats" aria-label="{{ __('Content overview') }}">
                <article class="admin-stat">
                    <span class="admin-stat__value">{{ $publishedPageCount }}</span>
                    <span>{{ __('Published pages') }}</span>
                </article>
                <article class="admin-stat">
                    <span class="admin-stat__value">{{ $draftPageCount }}</span>
                    <span>{{ __('Draft pages') }}</span>
                </article>
                <article class="admin-stat">
                    <span class="admin-stat__value">{{ $enabledModuleCount }}</span>
                    <span>{{ __('Enabled modules') }}</span>
                </article>
                <article class="admin-stat">
                    <span class="admin-stat__value">{{ $wmsModuleCount }}</span>
                    <span>{{ __('WMS modules') }}</span>
                </article>
            </section>
        </main>
    </div>
@endsection
