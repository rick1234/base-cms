@extends('layouts.admin')

@php
    $isExisting = (bool) $country->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $country->name]) : __('Toevoegen');
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="country-form" type="submit">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="country-form" name="saveAndStay" type="submit" value="1">
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

            <form id="country-form" name="edit-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $country->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $country->id }}">
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
                                        <input id="name" name="name" type="text" value="{{ old('name', $country->name) }}" required>
                                        @include('admin.content.partials.field-error', ['field' => 'name'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="slug">{{ __('Slug') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="slug" name="slug" type="text" value="{{ old('slug', $country->slug) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'slug'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="status">{{ __('Actief') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="status" name="status">
                                            <option value="inactive" @selected(old('status', $country->status) === 'inactive')>{{ __('Nee') }}</option>
                                            <option value="active" @selected(old('status', $country->status ?: 'active') === 'active')>{{ __('Ja') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <label class="country-toggle-option">
                                    <input type="hidden" name="is_enabled" value="0">
                                    <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $country->is_enabled ?? true))>
                                    <span class="checkbox"></span>
                                    {{ __('Available for website use') }}
                                </label>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="description">{{ __('Omschrijving') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <textarea id="description" name="description">{{ old('description', $country->description) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <h2 class="title">{{ __('ISO data') }}</h2>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="iso2">{{ __('Code') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="iso2" name="iso2" maxlength="2" type="text" value="{{ old('iso2', $country->iso2) }}" required>
                                        @include('admin.content.partials.field-error', ['field' => 'iso2'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="iso3">{{ __('ISO-3') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="iso3" name="iso3" maxlength="3" type="text" value="{{ old('iso3', $country->iso3) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'iso3'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="numeric_code">{{ __('Numeric code') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="numeric_code" name="numeric_code" maxlength="3" type="text" value="{{ old('numeric_code', $country->numeric_code) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'numeric_code'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="currency_code">{{ __('Currency') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="currency_code" name="currency_code" maxlength="3" type="text" value="{{ old('currency_code', $country->currency_code) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'currency_code'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="region_code">{{ __('Regio') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="region_code" name="region_code">
                                            <option value="">{{ __('Selecteer') }}</option>
                                            @foreach ($regionOptions as $code => $label)
                                                <option value="{{ $code }}" @selected(old('region_code', $country->region_code) === $code)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @include('admin.content.partials.field-error', ['field' => 'region_code'])
                                    </div>
                                </div>

                                <label class="country-toggle-option">
                                    <input type="hidden" name="charges_vat" value="0">
                                    <input type="checkbox" name="charges_vat" value="1" @checked(old('charges_vat', $country->charges_vat))>
                                    <span class="checkbox"></span>
                                    {{ __('BTW') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

            </form>

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Gemaakt door') }}:</strong> {{ $country->creator?->fullName() ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($country->created_at)->format('d-m-Y H:i') }}</span>
                    @if ($country->updated_at)
                        <span><strong>{{ __('Aangepast op') }}:</strong> {{ $country->updated_at->format('d-m-Y H:i') }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
