@extends('layouts.admin')

@php
    $isExisting = (bool) $vacancy->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $vacancy->title]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $vacancy->categories->pluck('id')->all() : []);
    $metadata = (array) ($vacancy->metadata ?? []);
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="vacancy-form" type="submit">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="vacancy-form" name="saveAndStay" type="submit" value="1">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting)
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

            <form id="vacancy-form" name="edit-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $vacancy->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $vacancy->id }}">
                <input type="hidden" name="active_tab" value="{{ $activeTab }}">
                <input type="hidden" name="saveAndStay" value="0">

                @if ($activeTab === 'info')
                    <div class="main-section">
                        @include('admin.vacancies.partials.page-header', [
                            'title' => $title,
                            'section' => $pageName,
                        ])

                        @include('admin.vacancies.partials.item-tabs', [
                            'active' => $activeTab,
                            'vacancy' => $vacancy,
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
                                            <input id="title" name="title" type="text" value="{{ old('title', $vacancy->title) }}" required>
                                            @include('admin.content.partials.field-error', ['field' => 'title'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="locale">{{ __('Taal') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <x-admin.locale-radio-group name="locale" :selected="old('locale', $vacancy->locale)" id-prefix="vacancy_locale" />
                                            @include('admin.content.partials.field-error', ['field' => 'locale'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="status">{{ __('Status') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <select id="status" name="status">
                                                <option value="published" @selected(old('status', $vacancy->status) === 'published')>{{ __('Online') }}</option>
                                                <option value="draft" @selected(old('status', $vacancy->status) === 'draft')>{{ __('Offline') }}</option>
                                                <option value="archived" @selected(old('status', $vacancy->status) === 'archived')>{{ __('Archived') }}</option>
                                            </select>
                                            @include('admin.content.partials.field-error', ['field' => 'status'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="location">{{ __('Locatie') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="location" name="location" type="text" value="{{ old('location', $metadata['location'] ?? null) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'location'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="employment_type">{{ __('Dienstverband') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="employment_type" name="employment_type" type="text" value="{{ old('employment_type', $metadata['employment_type'] ?? null) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'employment_type'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="hours">{{ __('Uren') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="hours" name="hours" type="text" value="{{ old('hours', $metadata['hours'] ?? null) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'hours'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="salary">{{ __('Salaris') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="salary" name="salary" type="text" value="{{ old('salary', $metadata['salary'] ?? null) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'salary'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="contact_email">{{ __('Contact e-mail') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $metadata['contact_email'] ?? null) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'contact_email'])
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <h2 class="title">{{ __('Categorie') }}</h2>
                                    <div class="categories-tree">
                                        @include('admin.vacancies.partials.category-tree', [
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
                            <h3 class="sub-title">{{ __('Periode') }}</h3>
                            <div class="grid-row">
                                <div class="col-4">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="active_from">{{ __('Startdatum') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="active_from" name="active_from" type="date" value="{{ old('active_from', optional($vacancy->active_from)->format('Y-m-d')) }}">
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
                                            <input id="active_until" name="active_until" type="date" value="{{ old('active_until', optional($vacancy->active_until)->format('Y-m-d')) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'active_until'])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="main-section">
                        <h2 class="title">{{ __('Omschrijving') }}</h2>
                        <textarea id="body" name="body">{{ old('body', $vacancy->body) }}</textarea>
                        @include('admin.content.partials.field-error', ['field' => 'body'])
                    </div>
                @elseif ($activeTab === 'form')
                    <div class="main-section">
                        @include('admin.vacancies.partials.page-header', [
                            'title' => __('Formulier'),
                            'section' => $pageName,
                        ])

                        @include('admin.vacancies.partials.item-tabs', [
                            'active' => $activeTab,
                            'vacancy' => $vacancy,
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
                                            <option value="{{ $form->id }}" @selected((int) old('form_id', $vacancy->form_id) === $form->id)>{{ $form->name }}</option>
                                        @endforeach
                                    </select>
                                    @include('admin.content.partials.field-error', ['field' => 'form_id'])
                                </div>
                            </div>
                        @else
                            <div class="attachment-message">
                                <x-admin.material-icon name="info" />
                                <em>{{ __('Geen formulieren gevonden.') }}</em>
                            </div>
                        @endif
                    </div>
                @elseif ($activeTab === 'seo')
                    <div class="main-section">
                        @include('admin.vacancies.partials.page-header', [
                            'title' => __('SEO settings'),
                            'section' => $pageName,
                        ])

                        @include('admin.vacancies.partials.item-tabs', [
                            'active' => $activeTab,
                            'vacancy' => $vacancy,
                            'routeNames' => $routeNames,
                        ])

                        <span class="content-admin-screen-label">{{ __('SEO settings') }}</span>

                        <div class="grid">
                            <div class="grid-row">
                                <div class="col-12">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="meta_title">{{ __('Meta title') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="meta_title" name="meta_title" type="text" value="{{ old('meta_title', $vacancy->meta_title) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'meta_title'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="meta_description">{{ __('Meta omschrijving') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <textarea id="meta_description" name="meta_description">{{ old('meta_description', $vacancy->meta_description) }}</textarea>
                                            @include('admin.content.partials.field-error', ['field' => 'meta_description'])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </form>

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Gemaakt door') }}:</strong> {{ $vacancy->creator?->fullName() ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($vacancy->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
