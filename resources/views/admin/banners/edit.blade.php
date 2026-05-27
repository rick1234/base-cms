@extends('layouts.admin')

@php
    $isExisting = (bool) $banner->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $banner->displayTitle()]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $banner->categories->pluck('id')->all() : []);
    $translations = $isExisting ? $banner->translations->keyBy('locale') : collect();
    $metadata = $banner->metadata ?? [];
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="banner-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="banner-form" name="saveAndStay" type="submit" value="1">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting)
                    <form method="post" action="{{ route($routeNames['duplicate']) }}">
                        @csrf
                        <input type="hidden" name="itemId" value="{{ $banner->id }}">
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

            <form id="banner-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $banner->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $banner->id }}">
                <input type="hidden" name="saveAndStay" value="0">

                <div class="main-section">
                    @include('admin.banners.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <div class="content-section">
                        <div class="grid">
                            <div class="grid-row">
                                <div class="col-6">
                                    <h2 class="title">{{ __('Afbeelding') }}</h2>

                                    @if ($banner->image_path)
                                        <div class="banner-image-preview">
                                            <img src="{{ asset($banner->image_path) }}" alt="{{ $metadata['alt_text'] ?? __('Afbeelding') }}" class="banner-image">
                                        </div>
                                    @endif

                                    <div class="attachment-row form-item">
                                        <input name="image" id="banner_image" type="file" class="attachment-row-input button-only" accept="image/*">
                                        <label for="banner_image" class="attachment-label">
                                            <span class="admin-symbol admin-symbol-attachment" aria-hidden="true"></span>
                                            {{ __('Kies een bestand') }}
                                        </label>
                                    </div>

                                    @if ($banner->image_path)
                                        <label class="banner-delete-image-option">
                                            <input type="checkbox" name="delete_image" value="1">
                                            <span class="checkbox"></span>
                                            {{ __('Verwijder afbeelding') }}
                                        </label>
                                    @endif

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="alt_text">{{ __('Alt tekst') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="alt_text" name="alt_text" type="text" value="{{ old('alt_text', $metadata['alt_text'] ?? '') }}">
                                        </div>
                                    </div>
                                    @include('admin.content.partials.field-error', ['field' => 'image'])
                                </div>

                                <div class="col-6">
                                    <div class="content-section">
                                        <h2 class="title">{{ __('Opties') }}</h2>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="starts_at">{{ __('Startdatum') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input id="starts_at" name="starts_at" type="date" value="{{ old('starts_at', optional($banner->starts_at)->format('Y-m-d')) }}">
                                            </div>
                                        </div>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="ends_at">{{ __('Einddatum') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input id="ends_at" name="ends_at" type="date" value="{{ old('ends_at', optional($banner->ends_at)->format('Y-m-d')) }}">
                                                @include('admin.content.partials.field-error', ['field' => 'ends_at'])
                                            </div>
                                        </div>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="status">{{ __('Status') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <select id="status" name="status">
                                                    <option value="draft" @selected(old('status', $banner->status) === 'draft')>{{ __('Inactief') }}</option>
                                                    <option value="published" @selected(old('status', $banner->status) === 'published')>{{ __('Actief') }}</option>
                                                    <option value="archived" @selected(old('status', $banner->status) === 'archived')>{{ __('Archived') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="target">{{ __('Link doel') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <select id="target" name="target">
                                                    <option value="_self" @selected(old('target', $metadata['target'] ?? '_self') === '_self')>{{ __('Zelfde venster') }}</option>
                                                    <option value="_blank" @selected(old('target', $metadata['target'] ?? '_self') === '_blank')>{{ __('Nieuw venster') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <h2 class="title">{{ __('Selecteer een categorie') }}</h2>
                                    <div class="categories-tree">
                                        @include('admin.banners.partials.category-tree', [
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
                    <h2 class="title">{{ __('Talen') }}</h2>

                    <div class="banner-language-list">
                        @foreach ($locales as $locale => $label)
                            @php $translation = $translations->get($locale); @endphp
                            <section class="banner-language-panel">
                                <h3 class="sub-title">{{ $label }}</h3>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="translation_title_{{ $locale }}">{{ __('Titel') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="translation_title_{{ $locale }}" name="translations[{{ $locale }}][title]" type="text" value="{{ old('translations.'.$locale.'.title', $translation?->title) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="translation_subtitle_{{ $locale }}">{{ __('Subtitel') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="translation_subtitle_{{ $locale }}" name="translations[{{ $locale }}][subtitle]" type="text" value="{{ old('translations.'.$locale.'.subtitle', $translation?->subtitle) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="translation_link_{{ $locale }}">{{ __('Link') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="translation_link_{{ $locale }}" name="translations[{{ $locale }}][link_url]" type="text" value="{{ old('translations.'.$locale.'.link_url', $translation?->link_url) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="translation_button_{{ $locale }}">{{ __('Knop titel') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="translation_button_{{ $locale }}" name="translations[{{ $locale }}][button_text]" type="text" value="{{ old('translations.'.$locale.'.button_text', $translation?->button_text) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="translation_text_{{ $locale }}">{{ __('Tekst') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <textarea id="translation_text_{{ $locale }}" name="translations[{{ $locale }}][content]">{{ old('translations.'.$locale.'.content', $translation?->content) }}</textarea>
                                    </div>
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>
            </form>

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Auteur') }}:</strong> {{ $banner->created_by ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($banner->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
