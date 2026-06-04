@extends('layouts.admin')

@php
    $formModuleName = ($module['page_key'] ?? null) === 'edit' ? ($module['screen_name'] ?? $module['name']) : $module['name'];
    $pageTitle = $record ? __('Bewerk: :module', ['module' => $formModuleName]) : __('Toevoegen');
    $moduleIcon = str($module['folder'])
        ->before('/')
        ->replace('_', '-')
        ->lower()
        ->toString();
    $moduleIcon = config("cms_icons.modules.{$moduleIcon}", config('cms_icons.fallback', 'extension'));
@endphp

@section('title', $pageTitle)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container">
                <div class="buttons-container align-right">
                    <button class="btn btn-save" form="record-form" type="submit">
                        <x-admin.material-icon name="save" />
                        {{ __('Opslaan') }}
                    </button>
                    <a class="btn" href="{{ $backUrl }}">
                        <x-admin.material-icon name="arrow_back" />
                        {{ __('Terug') }}
                    </a>
                    @if ($deleteAction)
                        <form method="post" action="{{ $deleteAction }}">
                            @csrf
                            @method('delete')
                            <button class="btn btn-delete" type="submit">
                                <x-admin.material-icon name="delete" />
                                {{ __('Verwijderen') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="main-section">
                <div class="page-header">
                    <div class="page-header-title-container">
                        <div class="page-header-title-image-container">
                            <x-admin.material-icon class="is-large" :name="$moduleIcon" />
                        </div>
                        <strong>{{ $pageTitle }}</strong>
                    </div>
                </div>

                @if ($pages->isNotEmpty())
                    <div class="tab-menu">
                        @foreach ($pages as $page)
                            <a href="{{ route($routeNames['page'], [$page['folder'], $page['page_key'], 'id' => $record?->id]) }}">
                                {{ $page['name'] }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <form id="record-form" class="edit-form" method="post" action="{{ $action }}">
                    @csrf
                    @if ($method !== 'post')
                        @method($method)
                    @endif

                    @foreach ($columns as $column)
                        @php
                            $value = old($column, data_get($record, $column));
                        @endphp

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="{{ $column }}">{{ str($column)->replace('_', ' ')->title() }}</label>
                            </div>
                            <div class="form-item-input">
                                @if (str_starts_with($column, 'is_') || str_starts_with($column, 'can_') || $column === 'preserve_query')
                                    <input id="{{ $column }}" name="{{ $column }}" type="checkbox" value="1" @checked($value == 1)>
                                @elseif (str_contains($column, 'content') || str_contains($column, 'description') || in_array($column, ['body', 'intro', 'metadata', 'settings', 'payload', 'billing_address', 'shipping_address', 'validation_rules', 'configuration', 'columns'], true))
                                    <textarea id="{{ $column }}" name="{{ $column }}">{{ $value }}</textarea>
                                @elseif (str_ends_with($column, '_at') || str_ends_with($column, '_from') || str_ends_with($column, '_until') || in_array($column, ['date', 'starts_at', 'ends_at'], true))
                                    <input id="{{ $column }}" name="{{ $column }}" type="date" value="{{ $value }}">
                                @else
                                    <input id="{{ $column }}" name="{{ $column }}" type="text" value="{{ $value }}" @required($column === $module['title_column'])>
                                @endif

                                @error($column)
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </form>
            </div>
        </div>
    </div>
@endsection
