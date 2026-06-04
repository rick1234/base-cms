@extends('layouts.admin')

@php
    $isExisting = (bool) $download->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $download->name]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $download->categories->pluck('id')->all() : []);
    $hasUnlimitedLink = old('unlimited_link', $isExisting && $download->link_expires_after_minutes === null);
    $activeTab = $activeTab ?? 'general';
    $publicDownloadUrl = $isExisting ? route('frontend.downloads.show', ['download' => $download->publicRouteKey()]) : null;
    $storageTestResult = session('download_storage_test_result');
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                @if ($activeTab === 'general')
                    <button class="btn btn-save" form="download-form" type="submit">
                        <x-admin.material-icon name="save" />
                        {{ __('Opslaan') }}
                    </button>
                    <button class="btn btn-save-and-stay" form="download-form" name="saveAndStay" type="submit" value="1">
                        <x-admin.material-icon name="save" />
                        {{ __('Opslaan en blijven') }}
                    </button>
                @endif
                @if ($isExisting)
                    @if ($download->hasFile())
                        <form method="post" action="{{ route($routeNames['link.generate'], ['id' => $download->id]) }}">
                            @csrf
                            <button class="btn" type="submit">
                                <x-admin.material-icon name="link" />
                                {{ __('Genereer unieke URL') }}
                            </button>
                        </form>
                    @endif
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

            @include('admin.downloads.partials.item-tabs', [
                'active' => $activeTab,
                'download' => $download,
                'routeNames' => $routeNames,
            ])

            @if ($activeTab === 'general')
                <form id="download-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $download->id])) }}" accept-charset="UTF-8">
                    @csrf
                    <input type="hidden" name="id" value="{{ $download->id }}">
                    <input type="hidden" name="active_tab" value="general">
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
                                    <h2 class="title">{{ __('Categorie') }}</h2>
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
                                <a class="btn" href="{{ $publicDownloadUrl }}" target="_blank">
                                    <x-admin.material-icon name="download" />
                                    {{ __('Download bestand') }}
                                </a>
                            </div>
                        @endif

                        <div class="form-item">
                            <div class="attachment-row form-item-input">
                                <input id="download_file" name="file" type="file" class="attachment-row-input button-only">
                                <label for="download_file" class="attachment-label">
                                    <x-admin.material-icon name="attach_file" />
                                    {{ __('Kies een bestand') }}
                                </label>
                                @include('admin.content.partials.field-error', ['field' => 'file'])
                                @include('admin.content.partials.field-error', ['field' => 'bestand'])
                            </div>
                        </div>
                    </div>
                </form>
            @elseif ($activeTab === 'storage')
                <div class="main-section">
                    @include('admin.downloads.partials.page-header', [
                        'title' => __('Bestandsbeveiliging'),
                        'section' => $pageName,
                    ])

                    <form method="post" action="{{ route($routeNames['storage.test'], $download) }}">
                        @csrf
                        <button class="btn btn-preview" type="submit">
                            <x-admin.material-icon name="verified_user" />
                            {{ __('Bestandsbeveiliging testen') }}
                        </button>
                    </form>

                    @if (is_array($storageTestResult))
                        <div class="listing-container">
                            @foreach ($storageTestResult['checks'] as $check)
                                <div class="overview-row">
                                    <div class="overview-item status">
                                        <x-admin.material-icon :name="$check['passed'] ? 'check_circle' : 'error'" />
                                    </div>
                                    <div class="overview-item title">{{ $check['label'] }}</div>
                                    <div class="overview-item">{{ $check['detail'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @elseif ($activeTab === 'invites')
                <div class="main-section">
                    @include('admin.downloads.partials.page-header', [
                        'title' => __('E-mail uitnodigingen'),
                        'section' => $pageName,
                    ])

                    @if ($download->hasFile())
                        <form method="post" action="{{ route($routeNames['invites.send'], $download) }}">
                            @csrf
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="emails">{{ __('E-mailadressen') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <textarea id="emails" name="emails" required>{{ old('emails') }}</textarea>
                                    @include('admin.content.partials.field-error', ['field' => 'emails'])
                                    @include('admin.content.partials.field-error', ['field' => 'emails.*'])
                                </div>
                            </div>
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="message">{{ __('Bericht') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <textarea id="message" name="message">{{ old('message') }}</textarea>
                                    @include('admin.content.partials.field-error', ['field' => 'message'])
                                </div>
                            </div>
                            <button class="btn btn-add" type="submit">
                                <x-admin.material-icon name="send" />
                                {{ __('Uitnodigingen versturen') }}
                            </button>
                        </form>
                    @else
                        <div class="attachment-message">
                            <x-admin.material-icon name="info" />
                            <em>{{ __('Save a file before sending download invites.') }}</em>
                        </div>
                    @endif

                    <h2 class="title">{{ __('Verstuurde uitnodigingen') }}</h2>
                    <div class="listing-container">
                        @forelse ($accessTokens->where('purpose', 'invite') as $token)
                            <div class="overview-row">
                                <div class="overview-item title">{{ $token->email }}</div>
                                <div class="overview-item">{{ __('Downloads') }}: {{ $token->used_count }}</div>
                                <div class="overview-item">{{ optional($token->last_used_at)->format('d-m-Y H:i') ?: '-' }}</div>
                            </div>
                        @empty
                            <div class="overview-row">
                                <div class="overview-item title">{{ __('Er zijn geen uitnodigingen gevonden.') }}</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            @elseif ($activeTab === 'log')
                <div class="main-section">
                    @include('admin.downloads.partials.page-header', [
                        'title' => __('Downloadlog'),
                        'section' => $pageName,
                    ])

                    <div class="listing-container">
                        <div class="overview-row header">
                            <div class="overview-item title">{{ __('E-mailadres') }}</div>
                            <div class="overview-item">{{ __('IP-adres') }}</div>
                            <div class="overview-item">{{ __('Aantal downloads') }}</div>
                            <div class="overview-item">{{ __('Laatst gedownload') }}</div>
                        </div>
                        @forelse ($accessTokens->where('used_count', '>', 0) as $token)
                            <div class="overview-row">
                                <div class="overview-item title">{{ $token->email ?: '-' }}</div>
                                <div class="overview-item">{{ $token->last_ip_address ?: '-' }}</div>
                                <div class="overview-item">{{ $token->used_count }}</div>
                                <div class="overview-item">{{ optional($token->last_used_at)->format('d-m-Y H:i') ?: '-' }}</div>
                            </div>
                        @empty
                            <div class="overview-row">
                                <div class="overview-item title">{{ __('Er zijn geen downloads gevonden.') }}</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            @elseif ($activeTab === 'qr')
                <div class="main-section">
                    @include('admin.downloads.partials.page-header', [
                        'title' => __('QR-code'),
                        'section' => $pageName,
                    ])

                    @if ($download->hasFile())
                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="download_public_url">{{ __('URL') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="download_public_url" type="text" readonly value="{{ $publicDownloadUrl }}">
                            </div>
                        </div>
                        <div class="download-qr-panel">
                            <img src="{{ route($routeNames['qr.svg'], $download) }}" alt="{{ __('QR-code') }}">
                            <a class="btn btn-preview" href="{{ route($routeNames['qr.svg'], $download) }}" target="_blank">
                                <x-admin.material-icon name="qr_code" />
                                {{ __('QR-code openen') }}
                            </a>
                        </div>
                    @else
                        <div class="attachment-message">
                            <x-admin.material-icon name="info" />
                            <em>{{ __('Sla eerst een bestand op voordat u een QR-code deelt.') }}</em>
                        </div>
                    @endif
                </div>
            @endif

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Gemaakt door') }}:</strong> {{ $download->creator?->fullName() ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($download->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
