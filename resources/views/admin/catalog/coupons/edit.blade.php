@extends('layouts.admin')

@php
    $isExisting = (bool) $coupon->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $coupon->name]) : __('Toevoegen');
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="catalog-coupon-form" type="submit">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
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

            <form id="catalog-coupon-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $coupon->id])) }}">
                @csrf
                <input type="hidden" name="id" value="{{ $coupon->id }}">

                <div class="main-section">
                    @include('admin.catalog.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <div class="content-section">
                        <h2 class="title">{{ __('Algemeen') }}</h2>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="name">{{ __('Naam') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="name" name="name" type="text" value="{{ old('name', $coupon->name) }}" required>
                                @include('admin.content.partials.field-error', ['field' => 'name'])
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="code">{{ __('Code') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="code" name="code" type="text" value="{{ old('code', $coupon->code) }}" required>
                                @include('admin.content.partials.field-error', ['field' => 'code'])
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="percentage_discount">{{ __('Kortingspercentage') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="percentage_discount" name="percentage_discount" type="number" min="0" max="100" value="{{ old('percentage_discount', $coupon->percentage_discount) }}" required>
                                @include('admin.content.partials.field-error', ['field' => 'percentage_discount'])
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="minimum_amount">{{ __('Minimum bedrag') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="minimum_amount" name="minimum_amount" type="number" step="0.01" min="0" value="{{ old('minimum_amount', number_format($coupon->minimum_amount / 100, 2, '.', '')) }}" required>
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="usage_mode">{{ __('Gebruik') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="usage_mode" name="usage_mode" type="text" value="{{ old('usage_mode', $coupon->usage_mode) }}" required>
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="starts_at">{{ __('Startdatum') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="starts_at" name="starts_at" type="date" value="{{ old('starts_at', optional($coupon->starts_at)->format('Y-m-d')) }}">
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="ends_at">{{ __('Einddatum') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="ends_at" name="ends_at" type="date" value="{{ old('ends_at', optional($coupon->ends_at)->format('Y-m-d')) }}">
                                @include('admin.content.partials.field-error', ['field' => 'ends_at'])
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="is_active">{{ __('Status') }}</label>
                            </div>
                            <div class="form-item-input">
                                <label>
                                    <input name="is_active" type="hidden" value="0">
                                    <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $coupon->is_active))>
                                    <span class="checkbox"></span>
                                    {{ __('Actief') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
