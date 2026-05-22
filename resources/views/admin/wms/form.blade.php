@extends('layouts.admin')

@php
    $formModuleName = ($module['page_key'] ?? null) === 'edit' ? ($module['screen_name'] ?? $module['name']) : $module['name'];
@endphp

@section('title', $record ? __('Edit :module', ['module' => $formModuleName]) : __('Create :module', ['module' => $formModuleName]))

@section('body')
    <div class="admin-layout">
        @include('admin.partials.navigation')

        <main class="admin-main">
            <div class="admin-page-header">
                <div>
                    <h1>{{ $record ? __('Edit :module', ['module' => $formModuleName]) : __('Create :module', ['module' => $formModuleName]) }}</h1>
                    <p>{{ $module['legacy_path'] }} / {{ $module['table'] }}</p>
                </div>
                <div class="form-actions">
                    <a class="button button--secondary" href="{{ $backUrl }}">{{ __('Back to screen') }}</a>
                    @if ($deleteAction)
                        <form method="post" action="{{ $deleteAction }}">
                            @csrf
                            @method('delete')
                            <button class="button button--danger" type="submit">{{ __('Delete') }}</button>
                        </form>
                    @endif
                </div>
            </div>

            <section class="admin-panel content-stack">
                @if ($pages->isNotEmpty())
                    <nav class="wms-page-tabs" aria-label="{{ __('WMS pages') }}">
                        @foreach ($pages as $page)
                            <a class="wms-page-tabs__link" href="{{ route('wms.modules.page', [$page['folder'], $page['page_key'], 'id' => $record?->id]) }}">
                                {{ $page['name'] }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                @if (session('status'))
                    <p class="notice notice--success">{{ session('status') }}</p>
                @endif

                <form class="form-stack" method="post" action="{{ $action }}">
                    @csrf
                    @if ($method !== 'post')
                        @method($method)
                    @endif

                    <div class="form-grid">
                        @foreach ($columns as $column)
                            @php
                                $value = old($column, data_get($record, $column));
                            @endphp
                            <div class="field">
                                <label class="field__label" for="{{ $column }}">{{ str($column)->replace('_', ' ')->title() }}</label>

                                @if (str_starts_with($column, 'is_') || str_starts_with($column, 'can_') || $column === 'preserve_query')
                                    <label class="field__check">
                                        <input id="{{ $column }}" name="{{ $column }}" type="checkbox" value="1" @checked($value == 1)>
                                        {{ __('Enabled') }}
                                    </label>
                                @elseif (str_contains($column, 'content') || str_contains($column, 'description') || in_array($column, ['body', 'intro', 'metadata', 'settings', 'payload', 'billing_address', 'shipping_address', 'validation_rules', 'configuration', 'columns'], true))
                                    <textarea class="field__textarea" id="{{ $column }}" name="{{ $column }}">{{ $value }}</textarea>
                                @elseif (str_ends_with($column, '_at') || str_ends_with($column, '_from') || str_ends_with($column, '_until') || in_array($column, ['date', 'starts_at', 'ends_at'], true))
                                    <input class="field__input" id="{{ $column }}" name="{{ $column }}" type="date" value="{{ $value }}">
                                @else
                                    <input class="field__input" id="{{ $column }}" name="{{ $column }}" type="text" value="{{ $value }}" @required($column === $module['title_column'])>
                                @endif

                                @error($column)
                                    <p class="field__error">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="form-actions">
                        <button class="button" type="submit">{{ __('Save record') }}</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
@endsection
