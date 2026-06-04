@extends('layouts.admin')

@php
    $isExisting = (bool) $category->id;
    $title = $isExisting ? __('Edit page category') : __('Toevoegen');
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
                <button class="btn btn-save" form="content-category-form" type="submit">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
                </button>
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
                </a>
            </div>

            <form id="content-category-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $category->id])) }}">
                @csrf
                <input type="hidden" name="id" value="{{ $category->id }}">

                <div class="main-section">
                    @include('admin.content.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6">
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

                            <div class="col-6">
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
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="description">{{ __('Omschrijving') }}</label>
                        </div>
                        <div class="form-item-input">
                            <textarea id="description" name="description">{{ old('description', $category->description) }}</textarea>
                        </div>
                    </div>

                    @if ($isExisting)
                        <div class="author-container">
                            <span><strong>{{ __('Gemaakt door') }}:</strong> {{ $category->creator?->fullName() ?? '-' }}</span>
                            <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($category->created_at)->format('d-m-Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
