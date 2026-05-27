@extends('layouts.admin')

@php
    $isExisting = (bool) $download->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $download->name]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $download->categories->pluck('id')->all() : []);
    $hasUnlimitedLink = old('unlimited_link', $isExisting && $download->link_expires_after_minutes === null);
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="download-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="download-form" name="saveAndStay" type="submit" value="1">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting)
                    @if ($download->hasFile())
                        <form method="post" action="{{ route($routeNames['link.generate'], ['id' => $download->id]) }}">
                            @csrf
                            <button class="btn" type="submit">
                                <span class="flaticon-link-symbol"></span>
                                {{ __('Genereer unieke URL') }}
                            </button>
                        </form>
                    @endif
                    <form method="post" action="{{ $deleteAction }}">
                        @csrf
                        @method('delete')
                        <button class="btn btn-remove" type="submit">
                            <span class="flaticon-close-button"></span>
                            {{ __('Verwijderen') }}
                        </button>
                    </form>
                @endif
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            <form id="download-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $download->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $download->id }}">
                <input type="hidden" name="saveAndStay" value="0">

                <div class="main-section">
                    @include('admin.downloads.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6">
                                <h2 class="title">{{ __('Algemeen') }}</h2>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="name">{{ __('Titel') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="name" name="name" type="text" value="{{ old('name', $download->name) }}" required>
                                        @include('admin.content.partials.field-error', ['field' => 'name'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="slug">{{ __('Slug') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="slug" name="slug" type="text" value="{{ old('slug', $download->slug) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'slug'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="active_from">{{ __('Startdatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="active_from" name="active_from" type="date" value="{{ old('active_from', optional($download->active_from)->format('Y-m-d')) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="active_until">{{ __('Einddatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="active_until" name="active_until" type="date" value="{{ old('active_until', optional($download->active_until)->format('Y-m-d')) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'active_until'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="status">{{ __('Status') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="status" name="status">
                                            <option value="inactive" @selected(old('status', $download->status) === 'inactive')>{{ __('Inactief') }}</option>
                                            <option value="active" @selected(old('status', $download->status) === 'active')>{{ __('Actief') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="description">{{ __('Omschrijving') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <textarea id="description" name="description">{{ old('description', $download->description) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <h2 class="title">{{ __('Selecteer een categorie') }}</h2>
                                <div class="categories-tree">
                                    @include('admin.downloads.partials.category-tree', [
                                        'categoriesByParent' => $categories->groupBy(fn ($category) => $category->parent_id ?: 0),
                                        'parentId' => 0,
                                        'linkedIds' => $linkedCategoryIds,
                                        'mode' => 'select',
                                        'routeNames' => $routeNames,
                                    ])
                                </div>
                                @include('admin.content.partials.field-error', ['field' => 'categories'])

                                <h2 class="title">{{ __('Beveiliging') }}</h2>
                                <label class="download-toggle-option">
                                    <input type="hidden" name="is_password_protected" value="0">
                                    <input type="checkbox" name="is_password_protected" value="1" @checked(old('is_password_protected', $download->is_password_protected))>
                                    <span class="checkbox"></span>
                                    {{ __('Wachtwoordbeveiliging') }}
                                </label>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="password">{{ __('Wachtwoord') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="password" name="password" type="password" autocomplete="new-password">
                                        @include('admin.content.partials.field-error', ['field' => 'password'])
                                    </div>
                                </div>

                                <h2 class="title">{{ __('Unieke URL') }}</h2>
                                <label class="download-toggle-option">
                                    <input type="hidden" name="unlimited_link" value="0">
                                    <input type="checkbox" name="unlimited_link" value="1" @checked($hasUnlimitedLink)>
                                    <span class="checkbox"></span>
                                    {{ __('Onbeperkt geldig') }}
                                </label>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="link_expires_after_minutes">{{ __('Geldig in minuten') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="link_expires_after_minutes" name="link_expires_after_minutes" type="number" min="1" value="{{ old('link_expires_after_minutes', $download->link_expires_after_minutes ?? 60) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'link_expires_after_minutes'])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-section">
                    <h2 class="title">{{ __('Bestand') }}</h2>

                    @if ($download->hasFile())
                        <div class="download-file-panel">
                            <strong>{{ __('Gekoppeld bestand') }}:</strong>
                            <span>{{ $download->original_filename }}</span>
                            <span>{{ number_format(($download->file_size ?? 0) / 1024, 1) }} KB</span>
                            <a class="btn" href="{{ route('frontend.downloads.show', ['download' => $download->publicRouteKey()]) }}" target="_blank">
                                <span class="flaticon-download-button"></span>
                                {{ __('Download bestand') }}
                            </a>
                        </div>
                    @endif

                    <div class="form-item">
                        <div class="attachment-row form-item-input">
                            <input id="download_file" name="file" type="file" class="attachment-row-input button-only">
                            <label for="download_file" class="attachment-label">
                                <span class="admin-symbol admin-symbol-attachment" aria-hidden="true"></span>
                                {{ __('Kies een bestand') }}
                            </label>
                            @include('admin.content.partials.field-error', ['field' => 'file'])
                            @include('admin.content.partials.field-error', ['field' => 'bestand'])
                        </div>
                    </div>
                </div>
            </form>

            @if ($isExisting)
                <div class="main-section">
                    <h2 class="title">{{ __('Download knop') }}</h2>
                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="download_button_url">{{ __('URL') }}</label>
                        </div>
                        <div class="form-item-input">
                            <input id="download_button_url" type="text" readonly value="{{ route('frontend.downloads.show', ['download' => $download->publicRouteKey()]) }}">
                        </div>
                    </div>
                    @if ($download->hasFile())
                        <form method="post" action="{{ route($routeNames['link.generate'], ['id' => $download->id]) }}">
                            @csrf
                            <button class="btn" type="submit">
                                <span class="flaticon-link-symbol"></span>
                                {{ __('Genereer unieke URL') }}
                            </button>
                        </form>
                    @endif
                </div>

                @if ($download->hasFile())
                    <div class="main-section">
                        <form method="post" action="{{ route($routeNames['file.delete'], ['id' => $download->id]) }}">
                            @csrf
                            <button class="btn btn-remove" type="submit">
                                <span class="flaticon-close-button"></span>
                                {{ __('Verwijder bestand') }}
                            </button>
                        </form>
                    </div>
                @endif

                <div class="author-container">
                    <span><strong>{{ __('Auteur') }}:</strong> {{ $download->created_by ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($download->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
