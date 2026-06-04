@extends('layouts.admin')

@php
    $isExisting = (bool) $category->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $category->name]) : __('Toevoegen');
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                @include('admin.partials.category-related-items-button')
                <button class="btn btn-save" form="download-category-form" type="submit">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
                </button>
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
                </a>
            </div>

            <form id="download-category-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $category->id])) }}">
                @csrf
                <input type="hidden" name="id" value="{{ $category->id }}">

                <div class="main-section">
                    @include('admin.downloads.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6">
                                <h2 class="title">{{ __('Categorie') }}</h2>

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
                                        <label for="status">{{ __('Status') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="status" name="status">
                                            <option value="active" @selected(old('status', $category->status) === 'active')>{{ __('Actief') }}</option>
                                            <option value="inactive" @selected(old('status', $category->status) === 'inactive')>{{ __('Inactief') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <label class="download-toggle-option">
                                    <input type="hidden" name="is_hidden_from_navigation" value="0">
                                    <input type="checkbox" name="is_hidden_from_navigation" value="1" @checked(old('is_hidden_from_navigation', $category->is_hidden_from_navigation))>
                                    <span class="checkbox"></span>
                                    {{ __('Verberg in navigatie') }}
                                </label>

                                <h2 class="title">{{ __('Omschrijving') }}</h2>
                                <textarea id="description" name="description">{{ old('description', $category->description) }}</textarea>
                            </div>

                            <div class="col-6">
                                <h2 class="title">{{ __('Hoofdcategorie') }}</h2>
                                <label>
                                    <input type="radio" name="parent_id" value="" @checked(! old('parent_id', request('parent', $category->parent_id)))>
                                    <span class="checkbox"></span>
                                    {{ __('Geen hoofdcategorie') }}
                                </label>
                                <div class="categories-tree">
                                    @include('admin.downloads.partials.category-tree', [
                                        'categoriesByParent' => $categoriesByParent,
                                        'parentId' => 0,
                                        'mode' => 'parent',
                                        'selectedParentId' => (int) old('parent_id', request('parent', $category->parent_id)),
                                        'currentCategoryId' => $category->id,
                                        'routeNames' => $routeNames,
                                    ])
                                </div>
                                @include('admin.content.partials.field-error', ['field' => 'parent_id'])
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
