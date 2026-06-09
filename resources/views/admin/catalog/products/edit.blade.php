@extends('layouts.admin')

@php
    $isExisting = (bool) $product->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $product->name]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $product->categories->pluck('id')->all() : []);
    $requestedTab = request()->route('tab') ?: request()->query('tab');
    $activeTab = $isExisting && $requestedTab === 'seo' ? 'seo' : 'edit';
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="catalog-product-form" type="submit">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="catalog-product-form" name="saveAndStay" type="submit" value="1">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting)
                    <form method="post" action="{{ route($routeNames['duplicate']) }}">
                        @csrf
                        <input type="hidden" name="itemId" value="{{ $product->id }}">
                        <button class="btn btn-duplicate" type="submit">
                            <x-admin.material-icon name="content_copy" />
                            {{ __('Dupliceren') }}
                        </button>
                    </form>
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

            @include('admin.catalog.products.partials.tabs', [
                'product' => $product,
                'routeNames' => $routeNames,
                'activeTab' => $activeTab,
            ])

            <form id="catalog-product-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $product->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $product->id }}">
                <input type="hidden" name="active_tab" value="{{ $activeTab }}">
                <input type="hidden" name="saveAndStay" value="0">

                @if ($activeTab === 'edit')
                    <div class="main-section">
                        @include('admin.catalog.partials.page-header', [
                            'title' => $title,
                            'section' => $pageName,
                        ])

                        <span class="content-admin-screen-label">{{ $pageName }}</span>

                        <div class="content-section">
                            <div class="grid">
                                <div class="grid-row">
                                    <div class="col-6">
                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="sku">{{ __('Artikelnummer') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input id="sku" name="sku" type="text" value="{{ old('sku', $product->sku) }}">
                                                @include('admin.content.partials.field-error', ['field' => 'sku'])
                                            </div>
                                        </div>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="name">{{ __('Naam') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}" required>
                                                @include('admin.content.partials.field-error', ['field' => 'name'])
                                            </div>
                                        </div>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label id="catalog-product-description-label" for="description">{{ __('Omschrijving') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <div class="wysiwyg-editor" data-wysiwyg-editor>
                                                    <div class="wysiwyg-toolbar" role="toolbar" aria-label="{{ __('Tekstopmaak') }}">
                                                        <button type="button" data-wysiwyg-command="bold" aria-label="{{ __('Vet') }}" title="{{ __('Vet') }}">
                                                            <x-admin.material-icon name="format_bold" />
                                                        </button>
                                                        <button type="button" data-wysiwyg-command="italic" aria-label="{{ __('Cursief') }}" title="{{ __('Cursief') }}">
                                                            <x-admin.material-icon name="format_italic" />
                                                        </button>
                                                        <button type="button" data-wysiwyg-command="insertUnorderedList" aria-label="{{ __('Opsomming') }}" title="{{ __('Opsomming') }}">
                                                            <x-admin.material-icon name="format_list_bulleted" />
                                                        </button>
                                                        <button type="button" data-wysiwyg-command="insertOrderedList" aria-label="{{ __('Genummerde lijst') }}" title="{{ __('Genummerde lijst') }}">
                                                            <x-admin.material-icon name="format_list_numbered" />
                                                        </button>
                                                        <button type="button" data-wysiwyg-command="createLink" data-wysiwyg-prompt="{{ __('Link URL') }}" aria-label="{{ __('Link invoegen') }}" title="{{ __('Link invoegen') }}">
                                                            <x-admin.material-icon name="link" />
                                                        </button>
                                                    </div>
                                                    <div class="wysiwyg-surface" contenteditable="true" role="textbox" aria-labelledby="catalog-product-description-label" aria-multiline="true" data-wysiwyg-surface>{!! str(old('description', $product->description))->sanitizeHtml() !!}</div>
                                                    <textarea class="wysiwyg-hidden-input" id="description" name="description" hidden data-wysiwyg-input>{{ old('description', $product->description) }}</textarea>
                                                </div>
                                                @include('admin.content.partials.field-error', ['field' => 'description'])
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <h2 class="title">{{ __('Categorie') }}</h2>
                                        <div class="categories-tree">
                                            @include('admin.catalog.partials.category-tree', [
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
                        <div class="grid">
                            <h3 class="sub-title">{{ __('Opties') }}</h3>
                            <div class="grid-row">
                                <div class="col-6">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="brand_id">{{ __('Merk') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <select id="brand_id" name="brand_id">
                                                <option value="">{{ __('Geen merk') }}</option>
                                                @foreach ($brands as $brand)
                                                    <option value="{{ $brand->id }}" @selected((int) old('brand_id', $product->brand_id) === $brand->id)>{{ $brand->name }}</option>
                                                @endforeach
                                            </select>
                                            @include('admin.content.partials.field-error', ['field' => 'brand_id'])
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="price">{{ __('Prijs') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <div class="price-input-group">
                                                <span class="price-input-prefix" aria-hidden="true">&euro;</span>
                                                <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $product->priceForInput()) }}" required>
                                            </div>
                                            @include('admin.content.partials.field-error', ['field' => 'price'])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="main-section">
                        <div class="grid">
                            <h3 class="sub-title">{{ __('Periode') }}</h3>
                            <div class="grid-row">
                                <div class="col-4">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="active_from">{{ __('Startdatum') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="active_from" name="active_from" type="date" value="{{ old('active_from', optional($product->active_from)->format('Y-m-d')) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'active_from'])
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="active_until">{{ __('Einddatum') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="active_until" name="active_until" type="date" value="{{ old('active_until', optional($product->active_until)->format('Y-m-d')) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'active_until'])
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="status">{{ __('Status') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <select id="status" name="status">
                                                <option value="published" @selected(old('status', $product->status) === 'published')>{{ __('Online') }}</option>
                                                <option value="draft" @selected(old('status', $product->status) === 'draft')>{{ __('Offline') }}</option>
                                                <option value="archived" @selected(old('status', $product->status) === 'archived')>{{ __('Archived') }}</option>
                                            </select>
                                            @include('admin.content.partials.field-error', ['field' => 'status'])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="main-section">
                        @if ($isExisting)
                            <livewire:admin.attachment-manager module="catalog" :record-id="$product->id" :key="'catalog-attachments-'.$product->id" />
                        @else
                            @include('admin.partials.attachment-manager', [
                                'attachments' => $product->attachments,
                                'inputId' => 'catalog_attachment_files',
                            ])
                        @endif
                    </div>
                @elseif ($activeTab === 'seo')
                    <div class="main-section">
                        @include('admin.catalog.partials.page-header', [
                            'title' => __('SEO settings'),
                            'section' => $pageName,
                        ])

                        <span class="content-admin-screen-label">{{ __('SEO settings') }}</span>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="meta_title">{{ __('Meta title') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="meta_title" name="meta_title" type="text" value="{{ old('meta_title', $product->meta_title) }}">
                                @include('admin.content.partials.field-error', ['field' => 'meta_title'])
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="meta_description">{{ __('Meta omschrijving') }}</label>
                            </div>
                            <div class="form-item-input">
                                <textarea id="meta_description" name="meta_description">{{ old('meta_description', $product->meta_description) }}</textarea>
                                @include('admin.content.partials.field-error', ['field' => 'meta_description'])
                            </div>
                        </div>
                    </div>
                @endif
            </form>

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Gemaakt door') }}:</strong> {{ $product->creator?->fullName() ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($product->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
