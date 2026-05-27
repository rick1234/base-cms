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
                <button class="btn btn-save" form="form-category-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                @if ($isExisting)
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

            <form id="form-category-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $category->id])) }}">
                @csrf
                <input type="hidden" name="id" value="{{ $category->id }}">

                <div class="main-section">
                    @include('admin.forms.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-8">
                                <div class="content-section">
                                    <h2 class="title">{{ __('Algemeen') }}</h2>

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
                                </div>
                            </div>

                            <div class="col-4">
                                <h2 class="title">{{ __('Bovenliggende categorie') }}</h2>
                                <div class="categories-tree">
                                    <label>
                                        <input type="radio" name="parent_id" value="" @checked(! old('parent_id', request('parent', $category->parent_id)))>
                                        <span class="checkbox"></span>
                                        {{ __('Geen') }}
                                    </label>
                                    @include('admin.forms.partials.category-tree', [
                                        'categoriesByParent' => $categories->groupBy(fn ($treeCategory) => $treeCategory->parent_id ?: 0),
                                        'parentId' => 0,
                                        'selectedParentId' => (int) old('parent_id', request('parent', $category->parent_id)),
                                        'currentCategoryId' => $category->id,
                                        'mode' => 'parent',
                                        'routeNames' => $routeNames,
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
