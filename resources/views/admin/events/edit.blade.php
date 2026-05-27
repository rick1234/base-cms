@extends('layouts.admin')

@php
    $isExisting = (bool) $event->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $event->title]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $event->categories->pluck('id')->all() : []);
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="event-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="event-form" name="saveAndStay" type="submit" value="1">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting)
                    <form method="post" action="{{ route($routeNames['duplicate']) }}">
                        @csrf
                        <input type="hidden" name="itemId" value="{{ $event->id }}">
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

            <form id="event-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $event->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $event->id }}">
                <input type="hidden" name="saveAndStay" value="0">

                <div class="main-section">
                    @include('admin.events.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="content-section">
                        <h2 class="title">{{ __('Algemeen') }}</h2>

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
                                <select id="locale" name="locale">
                                    @foreach (['nl', 'en', 'de', 'fr'] as $locale)
                                        <option value="{{ $locale }}" @selected(old('locale', $event->locale) === $locale)>{{ strtoupper($locale) }}</option>
                                    @endforeach
                                </select>
                                @include('admin.content.partials.field-error', ['field' => 'locale'])
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-section">
                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6">
                                <h2 class="title">{{ __('Opties') }}</h2>
                                <h3 class="sub-title">{{ __('Evenement periode') }}</h3>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="starts_at">{{ __('Startdatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="starts_at" name="starts_at" type="date" value="{{ old('starts_at', optional($event->starts_at)->format('Y-m-d')) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'starts_at'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="ends_at">{{ __('Einddatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="ends_at" name="ends_at" type="date" value="{{ old('ends_at', optional($event->ends_at)->format('Y-m-d')) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'ends_at'])
                                    </div>
                                </div>

                                <h3 class="sub-title">{{ __('Publicatie periode') }}</h3>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="active_from">{{ __('Startdatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="active_from" name="active_from" type="date" value="{{ old('active_from', optional($event->active_from)->format('Y-m-d')) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'active_from'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="active_until">{{ __('Einddatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="active_until" name="active_until" type="date" value="{{ old('active_until', optional($event->active_until)->format('Y-m-d')) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'active_until'])
                                    </div>
                                </div>

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

                            <div class="col-6">
                                <h2 class="title">{{ __('Categorieen') }}</h2>
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
                                        <option value="{{ $form->id }}" @selected((int) old('form_id', $event->form_id) === $form->id)>{{ $form->name }}</option>
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

                <div class="main-section">
                    @if ($isExisting)
                        <livewire:admin.attachment-manager module="events" :record-id="$event->id" :key="'event-attachments-'.$event->id" />
                    @else
                        @include('admin.partials.attachment-manager', [
                            'attachments' => $event->attachments,
                            'inputId' => 'event_attachment_files',
                        ])
                    @endif
                </div>

                <div class="main-section">
                    <h2 class="title">{{ __('Evenement Onderdelen') }}</h2>

                    @if ($event->parts->isNotEmpty())
                        <div class="grid event-parts-list">
                            <div class="grid-row">
                                <div class="col-5"><strong>{{ __('Naam') }}</strong></div>
                                <div class="col-2"><strong>{{ __('Starttijd') }}</strong></div>
                                <div class="col-2"><strong>{{ __('Eindtijd') }}</strong></div>
                                <div class="col-2"><strong>{{ __('Datum') }}</strong></div>
                                <div class="col-1"><strong>{{ __('Verwijderen') }}</strong></div>
                            </div>
                            @foreach ($event->parts as $part)
                                <div class="grid-row">
                                    <div class="col-5">
                                        <div class="form-item-input">
                                            <input name="existing_parts[{{ $part->id }}][title]" type="text" value="{{ old("existing_parts.{$part->id}.title", $part->title) }}">
                                            <input name="existing_parts[{{ $part->id }}][sort_order]" type="hidden" value="{{ $part->sort_order }}">
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-item-input">
                                            <input name="existing_parts[{{ $part->id }}][starts_at]" type="time" value="{{ old("existing_parts.{$part->id}.starts_at", optional($part->starts_at)->format('H:i')) }}">
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-item-input">
                                            <input name="existing_parts[{{ $part->id }}][ends_at]" type="time" value="{{ old("existing_parts.{$part->id}.ends_at", optional($part->ends_at)->format('H:i')) }}">
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-item-input">
                                            <input name="existing_parts[{{ $part->id }}][date]" type="date" value="{{ old("existing_parts.{$part->id}.date", optional($part->starts_at)->format('Y-m-d')) }}">
                                        </div>
                                    </div>
                                    <div class="col-1">
                                        <div class="form-item-input">
                                            <label class="delete-saved-part">
                                                <input name="existing_parts[{{ $part->id }}][delete]" type="checkbox" value="1">
                                                <span class="flaticon-rubbish-bin-delete-button"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="attachment-message">
                            <span class="flaticon-rounded-info-button"></span>
                            <em>{{ __('Er zijn (nog) geen onderdelen toegevoegd') }}.</em>
                        </div>
                    @endif

                    <h3 class="sub-title">{{ __('Onderdeel toevoegen') }}</h3>
                    <div class="grid onderdeel-input-container">
                        <div class="grid-row">
                            <div class="col-5">
                                <div class="form-item-label">
                                    <label for="new_part_title_0">{{ __('Naam') }}</label>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-item-label">
                                    <label for="new_part_starts_at_0">{{ __('Starttijd') }}</label>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-item-label">
                                    <label for="new_part_ends_at_0">{{ __('Eindtijd') }}</label>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-item-label">
                                    <label for="new_part_date_0">{{ __('Datum') }}</label>
                                </div>
                            </div>
                        </div>
                        @for ($index = 0; $index < 2; $index++)
                            <div class="grid-row">
                                <div class="col-5">
                                    <div class="form-item">
                                        <div class="form-item-input">
                                            <input id="new_part_title_{{ $index }}" name="new_parts[{{ $index }}][title]" type="text" value="{{ old("new_parts.{$index}.title") }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-2">
                                    <div class="form-item">
                                        <div class="form-item-input">
                                            <input id="new_part_starts_at_{{ $index }}" name="new_parts[{{ $index }}][starts_at]" type="time" value="{{ old("new_parts.{$index}.starts_at") }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-2">
                                    <div class="form-item">
                                        <div class="form-item-input">
                                            <input id="new_part_ends_at_{{ $index }}" name="new_parts[{{ $index }}][ends_at]" type="time" value="{{ old("new_parts.{$index}.ends_at") }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-2">
                                    <div class="form-item">
                                        <div class="form-item-input">
                                            <input id="new_part_date_{{ $index }}" name="new_parts[{{ $index }}][date]" type="date" value="{{ old("new_parts.{$index}.date", optional($event->starts_at)->format('Y-m-d')) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="main-section">
                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="intro">{{ __('Intro') }}</label>
                        </div>
                        <div class="form-item-input">
                            <textarea id="intro" name="intro">{{ old('intro', $event->intro) }}</textarea>
                        </div>
                    </div>
                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="body">{{ __('Body') }}</label>
                        </div>
                        <div class="form-item-input">
                            <textarea id="body" name="body">{{ old('body', $event->body) }}</textarea>
                        </div>
                    </div>
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

                <div class="main-section">
                    <div class="section-container">
                        <h2 class="title">{{ __('Fotoalbum') }}</h2>
                        @if ($isExisting)
                            <div class="plupload-container">
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="event_image">{{ __('Afbeelding') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="event_image" name="image" type="file" accept="image/*">
                                        <input name="image_caption" type="text" placeholder="{{ __('Titel') }}">
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="attachment-message">
                                <span class="flaticon-rounded-info-button"></span>
                                <em>{{ __('Sla het evenement eerst op voordat u afbeeldingen toevoegt.') }}</em>
                            </div>
                        @endif
                    </div>
                </div>
            </form>

            @if ($isExisting)
                <div class="main-section">
                    <h3 class="sub-title">{{ __('Reeds gekoppelde fotos') }}</h3>
                    @include('admin.content.partials.media-items', [
                        'images' => $event->images,
                        'deleteRoute' => $routeNames['image.delete'],
                        'updateNameRoute' => $routeNames['image.update-name'],
                    ])
                </div>

                <div class="author-container">
                    <span><strong>{{ __('Auteur') }}:</strong> {{ $event->created_by ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($event->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
