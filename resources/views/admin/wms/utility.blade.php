@extends('layouts.admin')

@section('title', $module['name'])

@section('body')
    <div class="admin-layout">
        @include('admin.partials.navigation')

        <main class="admin-main">
            <div class="admin-page-header">
                <div>
                    <h1>{{ $module['name'] }}</h1>
                    <p>{{ $module['description'] }}</p>
                </div>
                <a class="button button--secondary" href="{{ $backUrl }}">{{ __('Back') }}</a>
            </div>

            <section class="admin-panel content-stack">
                @if ($pages->isNotEmpty())
                    <nav class="wms-page-tabs" aria-label="{{ __('WMS pages') }}">
                        @foreach ($pages as $page)
                            <a class="wms-page-tabs__link" href="{{ route('wms.modules.page', [$page['folder'], $page['page_key']]) }}">
                                {{ $page['name'] }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                <dl class="wms-module-details">
                    <div>
                        <dt>{{ __('Legacy path') }}</dt>
                        <dd>{{ $module['legacy_path'] }}</dd>
                    </div>
                    @if ($module['table'] !== '')
                        <div>
                            <dt>{{ __('Laravel table') }}</dt>
                            <dd>{{ $module['table'] }}</dd>
                        </div>
                    @endif
                    @if (count($module['legacy_tables']) > 0)
                        <div>
                            <dt>{{ __('Legacy tables') }}</dt>
                            <dd>{{ implode(', ', $module['legacy_tables']) }}</dd>
                        </div>
                    @endif
                </dl>

                <p>{{ __('This legacy WMS page has been recreated as a safe Laravel utility screen. It does not execute old side-effect PHP code directly.') }}</p>

                @if ($rows->count() > 0 && count($columns) > 0)
                    <table class="admin-table">
                        <thead>
                            <tr>
                                @foreach ($columns as $column)
                                    <th>{{ str($column)->replace('_', ' ')->title() }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    @foreach ($columns as $column)
                                        <td>{{ data_get($row, $column) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{ $rows->links('admin.partials.pagination') }}
                @endif
            </section>
        </main>
    </div>
@endsection
