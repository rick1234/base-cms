@extends('layouts.admin')

@php
    $isExisting = (bool) $event->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $event->title]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $event->categories->pluck('id')->all() : []);
    $activeTab = $activeTab ?? 'general';
    $editorFormId = $activeTab === 'schedule' && $isExisting ? 'event-schedule-editor-form' : 'event-form';
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                @if ($activeTab !== 'images')
                    <button class="btn btn-save" form="{{ $editorFormId }}" type="submit">
                        <x-admin.material-icon name="save" />
                        {{ __('Opslaan') }}
                    </button>
                    <button class="btn btn-save-and-stay" form="{{ $editorFormId }}" name="saveAndStay" type="submit" value="1">
                        <x-admin.material-icon name="save" />
                        {{ __('Opslaan en blijven') }}
                    </button>
                @endif
                @if ($isExisting && $activeTab !== 'images')
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

            @if ($activeTab === 'schedule')
                <div class="main-section">
                    @include('admin.events.partials.page-header', [
                        'title' => __('Tijdschema'),
                        'section' => $pageName,
                    ])

                    @include('admin.events.partials.item-tabs', [
                        'active' => $activeTab,
                        'event' => $event,
                        'routeNames' => $routeNames,
                    ])

                    <span class="content-admin-screen-label">{{ __('Tijdschema') }}</span>

                    @if ($isExisting)
                        <livewire:admin.events.event-schedule-editor :event-id="$event->id" :key="'event-schedule-editor-'.$event->id" />
                    @else
                        <div class="attachment-message">
                            <x-admin.material-icon name="info" />
                            <em>{{ __('Sla het evenement eerst op voordat u het tijdschema opbouwt.') }}</em>
                        </div>
                    @endif
                </div>
            @elseif ($activeTab === 'images')
                <div class="main-section">
                    @include('admin.events.partials.page-header', [
                        'title' => __('Fotoalbum'),
                        'section' => $pageName,
                    ])

                    @include('admin.events.partials.item-tabs', [
                        'active' => $activeTab,
                        'event' => $event,
                        'routeNames' => $routeNames,
                    ])

                    <span class="content-admin-screen-label">{{ __('Fotoalbum') }}</span>

                    @if ($isExisting)
                        <livewire:admin.events.event-image-album :event="$event" :key="'event-image-album-'.$event->id" />
                    @else
                        <div class="attachment-message">
                            <x-admin.material-icon name="info" />
                            <em>{{ __('Sla het evenement eerst op voordat u afbeeldingen toevoegt.') }}</em>
                        </div>
                    @endif
                </div>
            @else
            <form id="event-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $event->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $event->id }}">
                <input type="hidden" name="active_tab" value="{{ $activeTab }}">
                <input type="hidden" name="saveAndStay" value="0">

                @if ($activeTab === 'general')
                    <div class="main-section">
                        @include('admin.events.partials.page-header', [
                            'title' => $title,
                            'section' => $pageName,
                        ])

                        @include('admin.events.partials.item-tabs', [
                            'active' => $activeTab,
                            'event' => $event,
                            'routeNames' => $routeNames,
                        ])

                        <span class="content-admin-screen-label">{{ $pageName }}</span>

                        <div class="grid">
                            <div class="grid-row">
                                <div class="col-6">
                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="title">{{ __('Titel') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input id="title" name="title" type="text" value="{{ old('title', $event->title) }}" required>
                                                @include('admin.content.partials.field-error', ['field' => 'title'])
                                            </div>
                                        </div>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="subtitle">{{ __('Subtitel') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle', $event->subtitle) }}">
                                                @include('admin.content.partials.field-error', ['field' => 'subtitle'])
                                            </div>
                                        </div>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="slug">{{ __('Slug') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input id="slug" name="slug" type="text" value="{{ old('slug', $event->slug) }}">
                                                @include('admin.content.partials.field-error', ['field' => 'slug'])
                                            </div>
                                        </div>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="locale">{{ __('Taal') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <x-admin.locale-radio-group name="locale" :selected="old('locale', $event->locale)" id-prefix="locale" />
                                                @include('admin.content.partials.field-error', ['field' => 'locale'])
                                            </div>
                                        </div>
                                </div>

                                <div class="col-6">
                                    <h2 class="title">{{ __('Categorie') }}</h2>
                                    <div class="categories-tree">
                                        @include('admin.events.partials.category-tree', [
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
                        <div class="grid">
                            <h3 class="sub-title">{{ __('Evenement periode') }}</h3>
                            <div class="grid-row">
                                <div class="col-6">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="starts_at">{{ __('Startdatum') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="starts_at" name="starts_at" type="date" value="{{ old('starts_at', optional($event->starts_at)->format('Y-m-d')) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'starts_at'])
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="ends_at">{{ __('Einddatum') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="ends_at" name="ends_at" type="date" value="{{ old('ends_at', optional($event->ends_at)->format('Y-m-d')) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'ends_at'])
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h3 class="sub-title">{{ __('Publicatie periode') }}</h3>
                            <div class="grid-row">
                                <div class="col-4">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="active_from">{{ __('Startdatum') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="active_from" name="active_from" type="date" value="{{ old('active_from', optional($event->active_from)->format('Y-m-d')) }}">
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
                                            <input id="active_until" name="active_until" type="date" value="{{ old('active_until', optional($event->active_until)->format('Y-m-d')) }}">
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
                                                <option value="published" @selected(old('status', $event->status) === 'published')>{{ __('Online') }}</option>
                                                <option value="draft" @selected(old('status', $event->status) === 'draft')>{{ __('Offline') }}</option>
                                                <option value="archived" @selected(old('status', $event->status) === 'archived')>{{ __('Archived') }}</option>
                                            </select>
                                            @include('admin.content.partials.field-error', ['field' => 'status'])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($activeTab === 'form')
                    <div class="main-section">
                        @include('admin.events.partials.page-header', [
                            'title' => __('Formulier'),
                            'section' => $pageName,
                        ])

                        @include('admin.events.partials.item-tabs', [
                            'active' => $activeTab,
                            'event' => $event,
                            'routeNames' => $routeNames,
                        ])

                        <span class="content-admin-screen-label">{{ __('Formulier') }}</span>

                        @if ($forms->isNotEmpty())
                            <div class="form-item">
                                <div class="form-item-label">
                                    <label for="form_id">{{ __('Kies een formulier') }}</label>
                                </div>
                                <div class="form-item-input">
                                    <select id="form_id" name="form_id">
                                        <option value="">{{ __('Geen formulier') }}</option>
                                        @foreach ($forms as $form)
                                            <option value="{{ $form->id }}" @selected((int) old('form_id', $event->form_id) === $form->id)>{{ $form->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @else
                            <p>{{ __('No forms found. Add a form first.') }}</p>
                            <a class="btn btn-add" href="{{ route(request()->routeIs('cms.*') ? 'cms.modules.index' : 'admin.modules.show', 'form') }}">
                                <x-admin.material-icon name="add" />
                                {{ __('Voeg nieuwe formulieren toe') }}
                            </a>
                        @endif
                    </div>
                @elseif ($activeTab === 'attachments')
                    <div class="main-section">
                        @include('admin.events.partials.page-header', [
                            'title' => __('Bijlagen'),
                            'section' => $pageName,
                        ])

                        @include('admin.events.partials.item-tabs', [
                            'active' => $activeTab,
                            'event' => $event,
                            'routeNames' => $routeNames,
                        ])

                        <span class="content-admin-screen-label">{{ __('Bijlagen') }}</span>

                        @if ($isExisting)
                            <livewire:admin.attachment-manager module="events" :record-id="$event->id" :key="'event-attachments-'.$event->id" />
                        @else
                            <div class="attachment-message">
                                <x-admin.material-icon name="info" />
                                <em>{{ __('Sla het evenement eerst op voordat u bijlagen toevoegt.') }}</em>
                            </div>
                        @endif
                    </div>
                @elseif ($activeTab === 'seo')
                    <div class="main-section">
                        @include('admin.events.partials.page-header', [
                            'title' => __('SEO settings'),
                            'section' => $pageName,
                        ])

                        @include('admin.events.partials.item-tabs', [
                            'active' => $activeTab,
                            'event' => $event,
                            'routeNames' => $routeNames,
                        ])

                        <span class="content-admin-screen-label">{{ __('SEO settings') }}</span>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="meta_description">{{ __('Meta omschrijving') }}</label>
                            </div>
                            <div class="form-item-input">
                                <textarea id="meta_description" name="meta_description">{{ old('meta_description', $event->meta_description) }}</textarea>
                                @include('admin.content.partials.field-error', ['field' => 'meta_description'])
                            </div>
                        </div>
                    </div>
                @endif
            </form>
            @endif

            @if ($isExisting && $activeTab === 'general')
                <div class="main-section">
                    <h2 class="title">{{ __('Blokken toevoegen') }}</h2>
                    <livewire:admin.content.content-block-editor owner-type="event" :event-id="$event->id" :key="'event-block-editor-'.$event->id" />
                </div>
            @endif

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Gemaakt door') }}:</strong> {{ $event->creator?->fullName() ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($event->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
