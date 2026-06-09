@extends('layouts.admin')

@section('title', $set ? __('Edit :record', ['record' => $set->name]) : __('Combination set toevoegen'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                @if ($set)
                    <form method="post" action="{{ route($routeNames['destroy'], ['id' => $set->id]) }}">
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

            <div class="main-section">
                @include('admin.catalog.partials.page-header', [
                    'title' => $set ? __('Edit :record', ['record' => $set->name]) : __('Combination set toevoegen'),
                    'section' => __('Combination sets'),
                ])

                <livewire:admin.catalog.catalog-combination-set-editor :set-id="$set?->id" />
            </div>
        </div>
    </div>
@endsection
