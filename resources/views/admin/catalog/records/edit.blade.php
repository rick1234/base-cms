@extends('layouts.admin')

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="catalog-record-form" type="submit">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
                </button>
                @if ($record->exists)
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

            <form id="catalog-record-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $record->id])) }}">
                @csrf
                <input type="hidden" name="id" value="{{ $record->id }}">

                <div class="main-section">
                    @include('admin.catalog.partials.page-header', [
                        'title' => $title,
                        'section' => $section,
                    ])

                    <div class="content-section">
                        <h2 class="title">{{ __('Algemeen') }}</h2>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="name">{{ __('Naam') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="name" name="name" type="text" value="{{ old('name', $record->name) }}" required>
                                @include('admin.content.partials.field-error', ['field' => 'name'])
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="slug">{{ __('Slug') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="slug" name="slug" type="text" value="{{ old('slug', $record->slug) }}">
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="description">{{ __('Omschrijving') }}</label>
                            </div>
                            <div class="form-item-input">
                                <textarea id="description" name="description">{{ old('description', $record->description) }}</textarea>
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="status">{{ __('Status') }}</label>
                            </div>
                            <div class="form-item-input">
                                <select id="status" name="status">
                                    <option value="active" @selected(old('status', $record->status) === 'active')>{{ __('Actief') }}</option>
                                    <option value="inactive" @selected(old('status', $record->status) === 'inactive')>{{ __('Inactief') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
