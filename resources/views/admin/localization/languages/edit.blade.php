@extends('layouts.admin')

@php
    $isExisting = (bool) $language->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $language->name]) : __('Toevoegen');
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="language-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="language-form" name="saveAndStay" type="submit" value="1">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan en blijven') }}
                </button>
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            <form id="language-form" name="edit-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $language->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $language->id }}">
                <input type="hidden" name="saveAndStay" value="0">

                <div class="main-section">
                    @include('admin.localization.partials.page-header', [
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
                                        <label for="name">{{ __('Naam') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="name" name="name" type="text" value="{{ old('name', $language->name) }}" required>
                                        @include('admin.content.partials.field-error', ['field' => 'name'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="native_name">{{ __('Native name') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="native_name" name="native_name" type="text" value="{{ old('native_name', $language->native_name) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'native_name'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="code">{{ __('Code') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="code" name="code" maxlength="16" type="text" value="{{ old('code', $language->code) }}" required>
                                        @include('admin.content.partials.field-error', ['field' => 'code'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="slug">{{ __('Slug') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="slug" name="slug" type="text" value="{{ old('slug', $language->slug) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'slug'])
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <h2 class="title">{{ __('Website settings') }}</h2>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="direction">{{ __('Direction') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="direction" name="direction">
                                            <option value="ltr" @selected(old('direction', $language->direction ?: 'ltr') === 'ltr')>{{ __('Left to right') }}</option>
                                            <option value="rtl" @selected(old('direction', $language->direction) === 'rtl')>{{ __('Right to left') }}</option>
                                        </select>
                                        @include('admin.content.partials.field-error', ['field' => 'direction'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="fallback_locale">{{ __('Fallback locale') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="fallback_locale" name="fallback_locale" maxlength="16" type="text" value="{{ old('fallback_locale', $language->fallback_locale) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'fallback_locale'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="status">{{ __('Actief') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="status" name="status">
                                            <option value="inactive" @selected(old('status', $language->status) === 'inactive')>{{ __('Nee') }}</option>
                                            <option value="active" @selected(old('status', $language->status ?: 'active') === 'active')>{{ __('Ja') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <label class="language-toggle-option">
                                    <input type="hidden" name="is_enabled" value="0">
                                    <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $language->is_enabled))>
                                    <span class="checkbox"></span>
                                    {{ __('Enabled for website') }}
                                </label>

                                <label class="language-toggle-option">
                                    <input type="hidden" name="is_default" value="0">
                                    <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $language->is_default))>
                                    <span class="checkbox"></span>
                                    {{ __('Default website language') }}
                                </label>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="description">{{ __('Omschrijving') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <textarea id="description" name="description">{{ old('description', $language->description) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Auteur') }}:</strong> {{ $language->created_by ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($language->created_at)->format('d-m-Y H:i') }}</span>
                    @if ($language->updated_at)
                        <span><strong>{{ __('Aangepast op') }}:</strong> {{ $language->updated_at->format('d-m-Y H:i') }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
