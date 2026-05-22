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
                <div class="form-actions">
                    @unless ($module['read_only'] ?? false)
                        @if (request()->routeIs('wms.*') && ($module['pages']['edit'] ?? false))
                            <a class="button" href="{{ route($routeNames['edit'], [$module['folder'], 'action' => 'nw']) }}">{{ __('Add') }}</a>
                        @else
                            <a class="button" href="{{ route($routeNames['create'], request()->routeIs('wms.*') ? $module['folder'] : $module['handle']) }}">{{ __('Add') }}</a>
                        @endif
                    @endunless
                    <a class="button button--secondary" href="{{ route($routeNames['index']) }}">{{ __('Back to WMS') }}</a>
                </div>
            </div>

            <section class="admin-panel content-stack">
                @if (session('status'))
                    <p class="notice notice--success">{{ session('status') }}</p>
                @endif

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
                    <div>
                        <dt>{{ __('Laravel table') }}</dt>
                        <dd>{{ $module['table'] }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('Legacy tables') }}</dt>
                        <dd>{{ implode(', ', $module['legacy_tables']) }}</dd>
                    </div>
                </dl>

                <div class="content-stack">
                    <h2>{{ __('Records') }}</h2>
                    @if ($rows->count() > 0 && count($columns) > 0)
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    @foreach ($columns as $column)
                                        <th>{{ str($column)->replace('_', ' ')->title() }}</th>
                                    @endforeach
                                    @unless ($module['read_only'] ?? false)
                                        <th>{{ __('Actions') }}</th>
                                    @endunless
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        @foreach ($columns as $column)
                                            <td>{{ data_get($row, $column) }}</td>
                                        @endforeach
                                        @unless ($module['read_only'] ?? false)
                                            <td>
                                                @php
                                                    $editTarget = request()->routeIs('wms.*')
                                                        ? [$module['folder'], 'id' => $row->id]
                                                        : [$module['handle'], $row->id];
                                                @endphp
                                                <div class="table-actions">
                                                    <a href="{{ route($routeNames['edit'], $editTarget) }}">{{ __('Edit') }}</a>
                                                    <form method="post" action="{{ route($routeNames['destroy'], [request()->routeIs('wms.*') ? $module['folder'] : $module['handle'], $row->id]) }}">
                                                        @csrf
                                                        @method('delete')
                                                        <button class="button-link" type="submit">{{ __('Delete') }}</button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endunless
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{ $rows->links('admin.partials.pagination') }}
                    @else
                        <p>{{ __('No records have been migrated or created for this screen yet.') }}</p>
                    @endif
                </div>
            </section>
        </main>
    </div>
@endsection
