@extends('layouts.admin')

@php
    $isExisting = (bool) $product->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $product->name]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $product->categories->pluck('id')->all() : []);
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

            <form id="catalog-product-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $product->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $product->id }}">
                <input type="hidden" name="saveAndStay" value="0">

                <div class="main-section">
                    @include('admin.catalog.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    @include('admin.catalog.products.partials.tabs', [
                        'product' => $product,
                        'routeNames' => $routeNames,
                        'activeTab' => 'edit',
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="content-section">
                        <h2 class="title">{{ __('Algemeen') }}</h2>

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
                                <label for="description">{{ __('Omschrijving') }}</label>
                            </div>
                            <div class="form-item-input">
                                <textarea id="description" name="description">{{ old('description', $product->description) }}</textarea>
                                @include('admin.content.partials.field-error', ['field' => 'description'])
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
                </div>

                <div class="main-section">
                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6">
                                <h2 class="title">{{ __('Opties') }}</h2>

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

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="promotion_id">{{ __('Promotie') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="promotion_id" name="promotion_id">
                                            <option value="">{{ __('Geen promotie') }}</option>
                                            @foreach ($promotions as $promotion)
                                                <option value="{{ $promotion->id }}" @selected((int) old('promotion_id', $product->promotion_id) === $promotion->id)>{{ $promotion->name }}</option>
                                            @endforeach
                                        </select>
                                        @include('admin.content.partials.field-error', ['field' => 'promotion_id'])
                                    </div>
                                </div>

                                <h3 class="sub-title">{{ __('Prijs') }}</h3>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="price">{{ __('Prijs') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $product->priceForInput()) }}" required>
                                        @include('admin.content.partials.field-error', ['field' => 'price'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="price_note">{{ __('Prijsopmerking') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <textarea id="price_note" name="price_note">{{ old('price_note', $product->price_note) }}</textarea>
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="is_on_sale">{{ __('Actie') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <label>
                                            <input id="is_on_sale" name="is_on_sale" type="checkbox" value="1" @checked(old('is_on_sale', $product->is_on_sale))>
                                            <span class="checkbox"></span>
                                            {{ __('Product is in de aanbieding') }}
                                        </label>
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="sale_price">{{ __('Actieprijs') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="sale_price" name="sale_price" type="number" step="0.01" min="0" value="{{ old('sale_price', $product->salePriceForInput()) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'sale_price'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="sale_price_note">{{ __('Actieprijsopmerking') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <textarea id="sale_price_note" name="sale_price_note">{{ old('sale_price_note', $product->sale_price_note) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <h2 class="title">{{ __('Categorieen') }}</h2>
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

                                <h3 class="sub-title">{{ __('Publicatie') }}</h3>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="active_from">{{ __('Startdatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="active_from" name="active_from" type="date" value="{{ old('active_from', optional($product->active_from)->format('Y-m-d')) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'active_from'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="active_until">{{ __('Einddatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="active_until" name="active_until" type="date" value="{{ old('active_until', optional($product->active_until)->format('Y-m-d')) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'active_until'])
                                    </div>
                                </div>

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

                                <h3 class="sub-title">{{ __('Actieperiode') }}</h3>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="sale_starts_at">{{ __('Startdatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="sale_starts_at" name="sale_starts_at" type="date" value="{{ old('sale_starts_at', optional($product->sale_starts_at)->format('Y-m-d')) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="sale_ends_at">{{ __('Einddatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="sale_ends_at" name="sale_ends_at" type="date" value="{{ old('sale_ends_at', optional($product->sale_ends_at)->format('Y-m-d')) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'sale_ends_at'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="can_be_engraved">{{ __('Graveren') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <label>
                                            <input id="can_be_engraved" name="can_be_engraved" type="checkbox" value="1" @checked(old('can_be_engraved', $product->can_be_engraved))>
                                            <span class="checkbox"></span>
                                            {{ __('Product kan gegraveerd worden') }}
                                        </label>
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
            </form>

            @if ($isExisting)
                <div class="main-section">
                    <h2 class="title">{{ __('Fotoalbum') }}</h2>
                    <form enctype="multipart/form-data" method="post" action="{{ route($routeNames['image.upload'], ['id' => $product->id]) }}">
                        @csrf
                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="catalog_product_image">{{ __('Afbeelding') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="catalog_product_image" name="image" type="file" accept="image/*">
                                <input name="caption" type="text" placeholder="{{ __('Titel') }}">
                                <button class="btn btn-add" type="submit">
                                    <x-admin.material-icon name="add" />
                                    {{ __('Uploaden') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="author-container">
                    <span><strong>{{ __('Gemaakt door') }}:</strong> {{ $product->creator?->fullName() ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($product->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
