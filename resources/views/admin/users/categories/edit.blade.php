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
                <button class="btn btn-save" form="user-category-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            <form id="user-category-form" name="edit-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $category->id])) }}">
                @csrf
                <input type="hidden" name="id" value="{{ $category->id }}">

                <div class="main-section">
                    @include('admin.users.partials.page-header', [
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
                        @include('admin.users.partials.category-tree', [
                            'categoriesByParent' => $categoriesByParent,
                            'parentId' => 0,
                            'mode' => 'parent',
                            'selectedParentId' => old('parent_id', $category->parent_id),
                            'currentCategoryId' => $category->id,
                            'routeNames' => $routeNames,
                        ])
                    </div>
                    @include('admin.content.partials.field-error', ['field' => 'parent_id'])
                </div>

                <div class="main-section">
                    <h2 class="title">{{ __('Omschrijving') }}</h2>
                    <textarea name="description">{{ old('description', $category->description) }}</textarea>
                </div>
            </form>

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Auteur') }}:</strong> {{ $category->created_by ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($category->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
