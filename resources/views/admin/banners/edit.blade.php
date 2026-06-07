@extends('layouts.admin')

@php
    $isExisting = (bool) $banner->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $banner->displayTitle()]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $banner->categories->pluck('id')->all() : []);
    $translations = $isExisting ? $banner->translations->keyBy('locale') : collect();
    $metadata = $banner->metadata ?? [];
    $activeTab = $activeTab ?? 'general';
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                @if (! in_array($activeTab, ['images', 'translations'], true))
                    <button class="btn btn-save" form="banner-form" type="submit">
                        <x-admin.material-icon name="save" />
                        {{ __('Opslaan') }}
                    </button>
                    <button class="btn btn-save-and-stay" form="banner-form" name="saveAndStay" type="submit" value="1">
                        <x-admin.material-icon name="save" />
                        {{ __('Opslaan en blijven') }}
                    </button>
                @endif
                @if ($isExisting)
                    <form method="post" action="{{ route($routeNames['duplicate']) }}">
                        @csrf
                        <input type="hidden" name="itemId" value="{{ $banner->id }}">
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

            @include('admin.banners.partials.item-tabs', [
                'active' => $activeTab,
                'banner' => $banner,
                'routeNames' => $routeNames,
            ])

            @if (! in_array($activeTab, ['images', 'translations'], true))
                <form id="banner-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $banner->id])) }}" accept-charset="UTF-8">
                    @csrf
                    <input type="hidden" name="id" value="{{ $banner->id }}">
                    <input type="hidden" name="active_tab" value="{{ $activeTab }}">
                    <input type="hidden" name="saveAndStay" value="0">

                @if ($activeTab === 'general')
                    <div class="main-section">
                        @include('admin.banners.partials.page-header', [
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

                                    <div class="col-6">
                                        <h2 class="title">{{ __('Categorie') }}</h2>
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
                        <div class="grid">
                            <h3 class="sub-title">{{ __('Publicatie periode') }}</h3>
                            <div class="grid-row">
                                <div class="col-4">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="starts_at">{{ __('Startdatum') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="starts_at" name="starts_at" type="date" value="{{ old('starts_at', optional($banner->starts_at)->format('Y-m-d')) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'starts_at'])
                                        </div>
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="ends_at">{{ __('Einddatum') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="ends_at" name="ends_at" type="date" value="{{ old('ends_at', optional($banner->ends_at)->format('Y-m-d')) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'ends_at'])
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
                                                <option value="published" @selected(old('status', $banner->status) === 'published')>{{ __('Online') }}</option>
                                                <option value="draft" @selected(old('status', $banner->status) === 'draft')>{{ __('Offline') }}</option>
                                                <option value="archived" @selected(old('status', $banner->status) === 'archived')>{{ __('Archived') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($activeTab === 'template')
                    <div class="main-section">
                        @include('admin.banners.partials.page-header', [
                            'title' => __('Template'),
                            'section' => $pageName,
                        ])

                        <span class="content-admin-screen-label">{{ __('Template') }}</span>

                        <div class="content-section">
                            <div class="template-assignment-toolbar">
                                <div>
                                    <h2 class="sub-title">{{ __('Template wireframe preview') }}</h2>
                                    <p class="form-item-description">{{ __('Use the highlighted section to check where this banner will appear in the active frontend template.') }}</p>
                                </div>
                                <a class="btn" href="{{ route('admin.templates.index') }}">
                                    <x-admin.material-icon name="dashboard_customize" />
                                    {{ __('Open template module') }}
                                </a>
                            </div>

                            <div class="template-wireframe-grid">
                                @forelse ($templatePreviews as $templatePreview)
                                    <x-admin.template-wireframe
                                        :template="$templatePreview"
                                        :selected-section="old('template_section', $banner->template_section)"
                                        compact
                                    />
                                @empty
                                    <div class="template-wireframe-empty">
                                        {{ __('No active templates found.') }}
                                    </div>
                                @endforelse
                            </div>

                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="template_section">{{ __('Defined section') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <select id="template_section" name="template_section">
                                        <option value="">{{ __('Selecteer') }}</option>
                                        @foreach ($templateSections as $sectionHandle => $sectionLabel)
                                            <option value="{{ $sectionHandle }}" @selected(old('template_section', $banner->template_section) === $sectionHandle)>{{ $sectionLabel }}</option>
                                        @endforeach
                                    </select>
                                    <p class="form-item-description">{{ __('Choose where this banner slider can be rendered by the active frontend template.') }}</p>
                                    @include('admin.content.partials.field-error', ['field' => 'template_section'])
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                </form>
            @elseif ($activeTab === 'images')
                <div class="main-section">
                    @include('admin.banners.partials.page-header', [
                        'title' => __('Afbeeldingen'),
                        'section' => $pageName,
                    ])

                    <span class="content-admin-screen-label">{{ __('Afbeeldingen') }}</span>

                    <div class="content-section">
                        <livewire:admin.banners.banner-image-album :banner="$banner" :key="'banner-image-album-'.$banner->id" />
                        @include('admin.content.partials.field-error', ['field' => 'images'])
                    </div>
                </div>
            @else
                <div class="main-section">
                    @include('admin.banners.partials.page-header', [
                        'title' => __('Vertalingen'),
                        'section' => $pageName,
                    ])

                    <span class="content-admin-screen-label">{{ __('Vertalingen') }}</span>

                    <div class="content-section">
                        <livewire:admin.banners.banner-translation-editor :banner="$banner" :locales="$locales" :key="'banner-translation-editor-'.$banner->id" />
                    </div>
                </div>
            @endif

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Gemaakt door') }}:</strong> {{ $banner->creator?->fullName() ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($banner->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
