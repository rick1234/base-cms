@extends('layouts.admin')

@section('title', __('Translations'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <livewire:admin.translations.translation-manager
            :initial-locale="$selectedLocale ?? null"
            :initial-editing-id="$editingId ?? null"
            :initial-create="$create ?? false"
            :initial-editor-title="$editorTitle ?? null"
        />
    </div>
@endsection
