@extends('layouts.admin')

@php
    $isExisting = (bool) $category->id;
    $title = $isExisting ? __('Bewerk: :name', ['name' => $category->name]) : __('Toevoegen');
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="content-category-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            @include('admin.content.partials.category-tabs', [
                'active' => 'info',
                'category' => $category,
                'routeNames' => $routeNames,
            ])

            <form id="content-category-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $category->id])) }}">
                @csrf
                <input type="hidden" name="id" value="{{ $category->id }}">

                <div class="main-section">
                    @include('admin.content.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="content-section">
                        <h1 class="title">{{ __('Categorie') }}</h1>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="name">{{ __('Naam') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="name" name="name" type="text" value="{{ old('name', $category->name) }}" required>
                                @include('admin.content.partials.field-error', ['field' => 'name'])
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="slug">{{ __('Slug') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="slug" name="slug" type="text" value="{{ old('slug', $category->slug) }}">
                                @include('admin.content.partials.field-error', ['field' => 'slug'])
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="status">{{ __('Actief') }}</label>
                            </div>
                            <div class="form-item-input">
                                <select id="status" name="status">
                                    <option value="active" @selected(old('status', $category->status) === 'active')>{{ __('Ja') }}</option>
                                    <option value="inactive" @selected(old('status', $category->status) === 'inactive')>{{ __('Nee') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">{{ __('Niet weergeven op de voorkant') }}</div>
                            <div class="form-item-input">
                                <label>
                                    <input name="is_hidden_from_navigation" type="checkbox" value="1" @checked(old('is_hidden_from_navigation', $category->is_hidden_from_navigation))>
                                    {{ __('Verborgen') }}
                                    <span class="checkbox"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="custom_url">{{ __('Aangepaste URL in navigatie') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="custom_url" name="custom_url" type="text" value="{{ old('custom_url', $category->custom_url) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-section">
                    <h2 class="title">{{ __('Parent categorie') }}</h2>
                    <div class="categories-tree">
                        <label>
                            <input type="radio" name="parent_id" value="" @checked(! old('parent_id', $category->parent_id))>
                            <span class="checkbox"></span>
                            {{ __('Hoofdcategorie') }}
                        </label>
                        @include('admin.content.partials.category-tree', [
                            'categoriesByParent' => $categoriesByParent,
                            'parentId' => 0,
                            'mode' => 'parent',
                            'selectedParentId' => old('parent_id', $category->parent_id),
                            'currentCategoryId' => $category->id,
                            'routeNames' => $routeNames,
                        ])
                    </div>
                </div>

                <div class="main-section">
                    <h2 class="title">{{ __('Omschrijving') }}</h2>
                    <textarea name="description">{{ old('description', $category->description) }}</textarea>
                </div>

                <div class="main-section">
                    <h2 class="title">{{ __('Meta omschrijving') }}</h2>
                    <textarea name="meta_description">{{ old('meta_description', $category->meta_description) }}</textarea>
                </div>

                <div class="main-section">
                    <div class="section-container">
                        <h2 class="title">{{ __('Afbeeldingen') }}</h2>
                        @if ($isExisting)
                            <div class="plupload-container">
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="category_image">{{ __('Afbeelding') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="category_image" name="images[]" type="file" accept="image/*">
                                        <input name="image_captions[]" type="text" placeholder="{{ __('Titel') }}">
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="attachment-message">
                                <span class="flaticon-rounded-info-button"></span>
                                <em>{{ __('Sla de categorie eerst op voordat u afbeeldingen toevoegt.') }}</em>
                            </div>
                        @endif
                    </div>
                </div>
            </form>

            @if ($isExisting)
                <div class="main-section">
                    <h3 class="sub-title">{{ __('Reeds gekoppelde afbeeldingen') }}</h3>
                    @include('admin.content.partials.media-items', [
                        'images' => $category->images,
                        'deleteRoute' => $routeNames['image.delete'],
                        'updateNameRoute' => $routeNames['image.update-name'],
                    ])
                </div>

                <div class="author-container">
                    <span><strong>{{ __('Auteur') }}:</strong> {{ $category->created_by ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($category->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
