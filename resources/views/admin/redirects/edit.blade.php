@extends('layouts.admin')

@php
    $isExisting = (bool) $redirect->id;
    $title = $isExisting ? __('Bewerk: /:source', ['source' => $redirect->source_path]) : __('Toevoegen');
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="redirect-form" type="submit">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="redirect-form" name="saveAndStay" type="submit" value="1">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting)
                    <form method="post" action="{{ $deleteAction }}">
                        @csrf
                        @method('delete')
                        <button class="btn btn-remove" type="submit">
                            <x-admin.material-icon name="delete" />
                            {{ __('Verwijderen') }}
                        </button>
                    </form>
                @endif
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
                </a>
            </div>

            <form id="redirect-form" name="edit-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $redirect->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $redirect->id }}">
                <input type="hidden" name="saveAndStay" value="0">

                <div class="main-section">
                    @include('admin.redirects.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="content-section">
                        <div class="grid">
                            <div class="grid-row">
                                <div class="col-6">
                                    <h2 class="title">{{ __('Redirect') }}</h2>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="source_path">{{ __('Oude URL') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="source_path" name="source_path" type="text" value="{{ old('source_path', $redirect->source_path) }}" placeholder="/oude-pagina" required>
                                            @include('admin.content.partials.field-error', ['field' => 'source_path'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="target_url">{{ __('Nieuwe URL') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="target_url" name="target_url" type="text" value="{{ old('target_url', $redirect->target_url) }}" placeholder="/nieuwe-pagina" required>
                                            @include('admin.content.partials.field-error', ['field' => 'target_url'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="description">{{ __('Omschrijving') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <textarea id="description" name="description">{{ old('description', $redirect->description) }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <h2 class="title">{{ __('Instellingen') }}</h2>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="status_code">{{ __('HTTP code') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <select id="status_code" name="status_code">
                                                @foreach ($statusCodes as $code => $label)
                                                    <option value="{{ $code }}" @selected((int) old('status_code', $redirect->status_code ?: 301) === $code)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <label class="redirect-toggle-option">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $redirect->is_active ?? true))>
                                        <span class="checkbox"></span>
                                        {{ __('Actief') }}
                                    </label>

                                    <label class="redirect-toggle-option">
                                        <input type="hidden" name="preserve_query" value="0">
                                        <input type="checkbox" name="preserve_query" value="1" @checked(old('preserve_query', $redirect->preserve_query))>
                                        <span class="checkbox"></span>
                                        {{ __('Querystring behouden') }}
                                    </label>

                                    @if ($isExisting)
                                        <dl class="cms-module-details">
                                            <dt>{{ __('Hits') }}</dt>
                                            <dd>{{ $redirect->hit_count ?? 0 }}</dd>
                                            <dt>{{ __('Laatst gebruikt') }}</dt>
                                            <dd>{{ optional($redirect->last_used_at)->format('d-m-Y H:i') ?: '-' }}</dd>
                                            <dt>{{ __('Status') }}</dt>
                                            <dd>{{ $redirect->statusLabel() }}</dd>
                                        </dl>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Gemaakt door') }}:</strong> {{ $redirect->creator?->fullName() ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($redirect->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
