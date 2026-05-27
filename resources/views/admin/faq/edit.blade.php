@extends('layouts.admin')

@php
    $isExisting = (bool) $faqItem->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $faqItem->question]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $faqItem->categories->pluck('id')->all() : []);
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="faq-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="faq-form" name="saveAndStay" type="submit" value="1">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting)
                    <form method="post" action="{{ route($routeNames['duplicate']) }}">
                        @csrf
                        <input type="hidden" name="itemId" value="{{ $faqItem->id }}">
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

            <form id="faq-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $faqItem->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $faqItem->id }}">
                <input type="hidden" name="saveAndStay" value="0">

                <div class="main-section">
                    @include('admin.faq.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    @include('admin.faq.partials.tabs', [
                        'faqItem' => $faqItem,
                        'routeNames' => $routeNames,
                        'activeTab' => 'edit',
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-8">
                                <div class="content-section">
                                    <h2 class="title">{{ __('Algemeen') }}</h2>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="question">{{ __('Vraag') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="question" name="question" type="text" value="{{ old('question', $faqItem->question) }}" required>
                                            @include('admin.content.partials.field-error', ['field' => 'question'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="slug">{{ __('Slug') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="slug" name="slug" type="text" value="{{ old('slug', $faqItem->slug) }}">
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
                                                    <option value="{{ $locale }}" @selected(old('locale', $faqItem->locale) === $locale)>{{ strtoupper($locale) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="status">{{ __('Status') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <select id="status" name="status">
                                                <option value="published" @selected(old('status', $faqItem->status) === 'published')>{{ __('Online') }}</option>
                                                <option value="draft" @selected(old('status', $faqItem->status) === 'draft')>{{ __('Offline') }}</option>
                                                <option value="archived" @selected(old('status', $faqItem->status) === 'archived')>{{ __('Archived') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-4">
                                <h2 class="title">{{ __('Categorie') }}</h2>
                                <div class="categories-tree">
                                    @include('admin.faq.partials.category-tree', [
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

                <div class="main-section">
                    <h2 class="title">{{ __('Publicatie') }}</h2>
                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6">
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="active_from">{{ __('Startdatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="active_from" name="active_from" type="date" value="{{ old('active_from', optional($faqItem->active_from)->format('Y-m-d')) }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="active_until">{{ __('Einddatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="active_until" name="active_until" type="date" value="{{ old('active_until', optional($faqItem->active_until)->format('Y-m-d')) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'active_until'])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-section">
                    <div class="content-section">
                        <h2 class="title">{{ __('Antwoord') }}</h2>
                        <div class="faq-item-answer-content">
                            <textarea id="body" name="body">{{ old('body', $faqItem->body) }}</textarea>
                            @include('admin.content.partials.field-error', ['field' => 'body'])
                        </div>
                    </div>
                </div>

                <div class="main-section">
                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="intro">{{ __('Intro') }}</label>
                        </div>
                        <div class="form-item-input">
                            <textarea id="intro" name="intro">{{ old('intro', $faqItem->intro) }}</textarea>
                        </div>
                    </div>
                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="meta_description">{{ __('Meta omschrijving') }}</label>
                        </div>
                        <div class="form-item-input">
                            <textarea id="meta_description" name="meta_description">{{ old('meta_description', $faqItem->meta_description) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="main-section">
                    @if ($isExisting)
                        <livewire:admin.attachment-manager module="faq" :record-id="$faqItem->id" :key="'faq-attachments-'.$faqItem->id" />
                    @else
                        @include('admin.partials.attachment-manager', [
                            'attachments' => $faqItem->attachments,
                            'inputId' => 'faq_attachment_files',
                        ])
                    @endif
                </div>
            </form>

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Auteur') }}:</strong> {{ $faqItem->created_by ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($faqItem->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
