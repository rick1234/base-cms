@extends('layouts.admin')

@section('title', __('Snel toevoegen'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="banner-bulk-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Uploaden') }}
                </button>
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            <form id="banner-bulk-form" method="post" action="{{ route($routeNames['bulk.upload']) }}" enctype="multipart/form-data">
                @csrf
                <div class="main-section">
                    @include('admin.banners.partials.page-header', [
                        'title' => __('Banner'),
                        'section' => $pageName,
                    ])

                    <div class="content-section">
                        <h2 class="title">{{ __('Selecteer bestanden') }}</h2>
                        <div class="attachment-row form-item">
                            <input name="banners[]" id="bulk_banners" type="file" class="attachment-row-input" accept="image/*" multiple required>
                            <label for="bulk_banners" class="attachment-label">
                                <span class="admin-symbol admin-symbol-attachment" aria-hidden="true"></span>
                                {{ __('Kies bestanden') }}
                            </label>
                        </div>
                        @include('admin.content.partials.field-error', ['field' => 'banners'])
                    </div>

                    <div class="content-section">
                        <h2 class="sub-title">{{ __('Plaats in categorie') }} ({{ __('optioneel') }})</h2>
                        <div class="categories-tree">
                            @include('admin.banners.partials.category-tree', [
                                'categoriesByParent' => $categories->groupBy(fn ($category) => $category->parent_id ?: 0),
                                'parentId' => 0,
                                'linkedIds' => [],
                                'mode' => 'select',
                                'routeNames' => $routeNames,
                            ])
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
