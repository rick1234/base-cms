@extends('layouts.admin')

@section('title', __('Edit page'))

@section('body')
    <div class="admin-layout">
        @include('admin.partials.navigation')

        <main class="admin-main">
            <div class="admin-page-header">
                <h1>{{ __('Edit page') }}</h1>
                <a class="button button-secondary" href="{{ route('admin.pages.index') }}">{{ __('Back to pages') }}</a>
            </div>

            <section class="admin-panel content-stack">

                @include('admin.pages.form', [
                    'page' => $page,
                    'action' => route('admin.pages.update', $page),
                    'method' => 'put',
                ])
            </section>
        </main>
    </div>
@endsection
