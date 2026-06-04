@extends('layouts.admin')

@section('title', __('Pages'))

@section('body')
    <div class="admin-layout">
        @include('admin.partials.navigation')

        <main class="admin-main">
            <div class="admin-page-header">
                <h1>{{ __('Pages') }}</h1>
                <a class="button" href="{{ route('admin.pages.create') }}">{{ __('New page') }}</a>
            </div>

            <section class="admin-panel">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Slug') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Updated') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pages as $page)
                            <tr>
                                <td>{{ $page->title }}</td>
                                <td>{{ $page->slug }}</td>
                                <td>{{ $page->status }}</td>
                                <td>{{ $page->updated_at?->format('Y-m-d') }}</td>
                                <td><a href="{{ route('admin.pages.edit', $page) }}">{{ __('Edit') }}</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">{{ __('No pages found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $pages->links('admin.partials.pagination') }}
            </section>
        </main>
    </div>
@endsection
