@extends('layouts.admin')

@php
    $isExisting = (bool) $contentItem->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $contentItem->title]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $contentItem->categories->pluck('id')->all() : []);
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                @if ($isExisting)
                    <form method="post" action="{{ route($routeNames['preview'], ['id' => $contentItem->id]) }}" target="_blank">
                        @csrf
                        <button class="btn btn-preview btn-icon-only" type="submit" aria-label="{{ __('Preview') }}" title="{{ __('Preview') }}">
                            <x-admin.material-icon name="preview" />
                        </button>
                    </form>
                @endif
                @if ($frontendUrl)
                    <a class="btn btn-live-page btn-icon-only" href="{{ $frontendUrl }}" target="_blank" rel="noopener" aria-label="{{ __('View live page') }}" title="{{ __('View live page') }}">
                        <x-admin.material-icon name="public" />
                    </a>
                @endif
                <button class="btn btn-save" form="content-item-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="content-item-form" name="saveAndStay" type="submit" value="1">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting)
                    <form method="post" action="{{ route($routeNames['duplicate']) }}">
                        @csrf
                        <input type="hidden" name="itemId" value="{{ $contentItem->id }}">
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

            @include('admin.content.partials.item-tabs', [
                'active' => 'info',
                'contentItem' => $contentItem,
                'routeNames' => $routeNames,
            ])

            <form id="content-item-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $contentItem->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $contentItem->id }}">
                <input type="hidden" name="saveAndStay" value="0">

                <div class="main-section">
                    @include('admin.content.partials.page-header', [
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
                                            <label for="title">{{ __('Titel') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="title" name="title" type="text" value="{{ old('title', $contentItem->title) }}" required>
                                            @include('admin.content.partials.field-error', ['field' => 'title'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="subtitle">{{ __('Subtitel') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle', $contentItem->subtitle) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'subtitle'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="slug">{{ __('URL') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <div class="url-input-group">
                                                <span class="url-input-base">{{ $frontendUrlBase }}</span>
                                                <input id="slug" name="slug" type="text" value="{{ old('slug', $contentItem->slug) }}">
                                            </div>
                                            @include('admin.content.partials.field-error', ['field' => 'slug'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="locale">{{ __('Taal') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <select id="locale" name="locale">
                                                @foreach (['nl', 'en', 'de', 'fr'] as $locale)
                                                    <option value="{{ $locale }}" @selected(old('locale', $contentItem->locale) === $locale)>{{ strtoupper($locale) }}</option>
                                                @endforeach
                                            </select>
                                            @include('admin.content.partials.field-error', ['field' => 'locale'])
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <h2 class="title">{{ __('Selecteer een categorie') }}</h2>
                                    <div class="categories-tree">
                                        @include('admin.content.partials.category-tree', [
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
                        <div class="grid-row">
                            <div class="col-6">
                                <h3 class="sub-title">{{ __('Periode') }}</h3>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="active_from">{{ __('Startdatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="active_from" name="active_from" type="date" value="{{ old('active_from', optional($contentItem->active_from)->format('Y-m-d')) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'active_from'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="active_until">{{ __('Einddatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="active_until" name="active_until" type="date" value="{{ old('active_until', optional($contentItem->active_until)->format('Y-m-d')) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'active_until'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="status">{{ __('Status') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="status" name="status">
                                            <option value="published" @selected(old('status', $contentItem->status) === 'published')>{{ __('Online') }}</option>
                                            <option value="draft" @selected(old('status', $contentItem->status) === 'draft')>{{ __('Offline') }}</option>
                                            <option value="archived" @selected(old('status', $contentItem->status) === 'archived')>{{ __('Archived') }}</option>
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
                        <livewire:admin.attachment-manager module="content" :record-id="$contentItem->id" :key="'content-attachments-'.$contentItem->id" />
                    @else
                        @include('admin.partials.attachment-manager', [
                            'attachments' => $contentItem->attachments,
                            'inputId' => 'content_attachment_files',
                        ])
                    @endif
                </div>

                <div class="main-section">
                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="meta_description">{{ __('Meta omschrijving') }}</label>
                        </div>
                        <div class="form-item-input">
                            <textarea id="meta_description" name="meta_description">{{ old('meta_description', $contentItem->meta_description) }}</textarea>
                            @include('admin.content.partials.field-error', ['field' => 'meta_description'])
                        </div>
                    </div>
                </div>

                <div class="main-section">
                    <h2 class="title">{{ __('Formulier') }}</h2>
                    @if ($forms->isNotEmpty())
                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="form_id">{{ __('Kies een formulier') }}</label>
                            </div>
                            <div class="form-item-input">
                                <select id="form_id" name="form_id">
                                    <option value="">{{ __('Geen formulier') }}</option>
                                    @foreach ($forms as $form)
                                        <option value="{{ $form->id }}" @selected((int) old('form_id', $contentItem->form_id) === $form->id)>{{ $form->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @else
                        <p>{{ __('Er zijn nog geen formulieren beschikbaar voeg eerst een formulier toe') }}</p>
                        <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.modules.index' : 'admin.modules.show', 'form') }}">
                            <span class="flaticon-add-plus-button"></span>
                            {{ __('Voeg nieuwe formulieren toe') }}
                        </a>
                    @endif
                </div>

            </form>

            @if ($isExisting)
                <div class="main-section">
                    <h2 class="title">{{ __('Blokken toevoegen') }}</h2>
                    <livewire:admin.content.content-block-editor :content-item-id="$contentItem->id" :key="'content-block-editor-'.$contentItem->id" />
                </div>
            @endif

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Auteur') }}:</strong> {{ $contentItem->created_by ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($contentItem->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
