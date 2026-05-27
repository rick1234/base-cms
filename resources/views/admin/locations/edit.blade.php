@extends('layouts.admin')

@php
    $isExisting = (bool) $location->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $location->name]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $location->categories->pluck('id')->all() : []);
    $metadata = $location->metadata ?? [];
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="location-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="location-form" name="saveAndStay" type="submit" value="1">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting)
                    <form method="post" action="{{ route($routeNames['duplicate']) }}">
                        @csrf
                        <input type="hidden" name="itemId" value="{{ $location->id }}">
                        <button class="btn btn-duplicate" type="submit">
                            <span class="flaticon-add-to-queue-button"></span>
                            {{ __('Dupliceren') }}
                        </button>
                    </form>
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

            <form id="location-form" name="edit-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $location->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $location->id }}">
                <input type="hidden" name="saveAndStay" value="0">

                <div class="main-section">
                    @include('admin.locations.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    @include('admin.locations.partials.tabs', [
                        'location' => $location,
                        'routeNames' => $routeNames,
                        'activeTab' => 'edit',
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="content-section">
                        <div class="grid">
                            <div class="grid-row">
                                <div class="col-6">
                                    <h2 class="title">{{ __('Vestiging') }}</h2>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="name">{{ __('Naam') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="name" name="name" type="text" value="{{ old('name', $location->name) }}" required>
                                            @include('admin.content.partials.field-error', ['field' => 'name'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="street_address">{{ __('Adres') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="street_address" name="street_address" type="text" value="{{ old('street_address', $location->street_address) }}">
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="postal_code">{{ __('Postcode') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $location->postal_code) }}">
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="city">{{ __('Plaats') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="city" name="city" type="text" value="{{ old('city', $location->city) }}">
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="country_code">{{ __('Landcode') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="country_code" name="country_code" type="text" value="{{ old('country_code', $location->country_code) }}">
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="email">{{ __('E-mail') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="email" name="email" type="email" value="{{ old('email', $location->email) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'email'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="phone">{{ __('Telefoon') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="phone" name="phone" type="text" value="{{ old('phone', $location->phone) }}">
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="website_url">{{ __('Website') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="website_url" name="website_url" type="text" value="{{ old('website_url', $location->website_url) }}">
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="chamber_of_commerce_number">{{ __('KVK nummer') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="chamber_of_commerce_number" name="chamber_of_commerce_number" type="text" value="{{ old('chamber_of_commerce_number', $location->chamber_of_commerce_number) }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <h2 class="title">{{ __('Opties') }}</h2>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="active_from">{{ __('Startdatum') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="active_from" name="active_from" type="date" value="{{ old('active_from', optional($location->active_from)->format('Y-m-d')) }}">
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="active_until">{{ __('Einddatum') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="active_until" name="active_until" type="date" value="{{ old('active_until', optional($location->active_until)->format('Y-m-d')) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'active_until'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="status">{{ __('Status') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <select id="status" name="status">
                                                <option value="active" @selected(old('status', $location->status) === 'active')>{{ __('Actief') }}</option>
                                                <option value="inactive" @selected(old('status', $location->status) === 'inactive')>{{ __('Inactief') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="sort_order">{{ __('Sortering') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $location->sort_order ?? 0) }}">
                                        </div>
                                    </div>

                                    <h2 class="title">{{ __('Selecteer een categorie') }}</h2>
                                    <div class="categories-tree">
                                        @include('admin.locations.partials.category-tree', [
                                            'categoriesByParent' => $categories->groupBy(fn ($category) => $category->parent_id ?: 0),
                                            'parentId' => 0,
                                            'linkedIds' => $linkedCategoryIds,
                                            'mode' => 'select',
                                            'routeNames' => $routeNames,
                                        ])
                                    </div>
                                    @include('admin.content.partials.field-error', ['field' => 'categories'])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-section">
                    <h2 class="title">{{ __('Omschrijving') }}</h2>
                    <textarea id="description" name="description">{{ old('description', $location->description) }}</textarea>

                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6">
                                <h2 class="title">{{ __('Google maps') }}</h2>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="latitude">{{ __('Latitude') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="latitude" name="latitude" type="text" value="{{ old('latitude', $location->latitude) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="longitude">{{ __('Longitude') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="longitude" name="longitude" type="text" value="{{ old('longitude', $location->longitude) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="map_info">{{ __('Google maps info') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <textarea id="map_info" name="map_info">{{ old('map_info', $location->map_info) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <h2 class="title">{{ __('SEO') }}</h2>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="slug">{{ __('Slug') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="slug" name="slug" type="text" value="{{ old('slug', $metadata['slug'] ?? '') }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="seo_title">{{ __('SEO titel') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="seo_title" name="seo_title" type="text" value="{{ old('seo_title', $metadata['seo_title'] ?? '') }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="meta_description">{{ __('Meta omschrijving') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <textarea id="meta_description" name="meta_description">{{ old('meta_description', $metadata['meta_description'] ?? '') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Auteur') }}:</strong> {{ $location->created_by ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($location->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
