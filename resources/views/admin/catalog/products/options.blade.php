@extends('layouts.admin')

@section('title', __('Product options'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="catalog-options-form" type="submit">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
                </button>
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
                </a>
            </div>

            <div class="main-section">
                @include('admin.catalog.partials.page-header', [
                    'title' => $product->name,
                    'section' => $pageName,
                ])

                @include('admin.catalog.products.partials.tabs', [
                    'product' => $product,
                    'routeNames' => $routeNames,
                    'activeTab' => 'options',
                ])

                <form id="catalog-options-form" method="post" action="{{ route($routeNames['options.save'], ['id' => $product->id]) }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $product->id }}">

                    <h2 class="title">{{ __('Product opties') }}</h2>
                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-2"><strong>{{ __('Taal') }}</strong></div>
                            <div class="col-3"><strong>{{ __('Label') }}</strong></div>
                            <div class="col-5"><strong>{{ __('Waarde') }}</strong></div>
                            <div class="col-2"><strong>{{ __('Verwijderen') }}</strong></div>
                        </div>
                        @foreach ($product->options as $index => $option)
                            <div class="grid-row">
                                <div class="col-2">
                                    <input type="hidden" name="options[{{ $index }}][id]" value="{{ $option->id }}">
                                    <x-admin.locale-radio-group
                                        name="options[{{ $index }}][locale]"
                                        :selected="old('options.'.$index.'.locale', $option->locale)"
                                        :id-prefix="'option_locale_'.$index"
                                    />
                                </div>
                                <div class="col-3">
                                    <input name="options[{{ $index }}][label]" type="text" value="{{ old("options.{$index}.label", $option->label) }}">
                                </div>
                                <div class="col-5">
                                    <textarea name="options[{{ $index }}][value]">{{ old("options.{$index}.value", $option->value) }}</textarea>
                                </div>
                                <div class="col-2">
                                    <label>
                                        <input name="options[{{ $index }}][delete]" type="checkbox" value="1">
                                        <span class="checkbox"></span>
                                        {{ __('Verwijderen') }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                        @for ($newIndex = 0; $newIndex < 3; $newIndex++)
                            @php $index = $product->options->count() + $newIndex; @endphp
                            <div class="grid-row">
                                <div class="col-2">
                                    <x-admin.locale-radio-group
                                        name="options[{{ $index }}][locale]"
                                        :selected="old('options.'.$index.'.locale', app()->getLocale())"
                                        :id-prefix="'option_locale_'.$index"
                                    />
                                </div>
                                <div class="col-3">
                                    <input name="options[{{ $index }}][label]" type="text" value="{{ old("options.{$index}.label") }}">
                                </div>
                                <div class="col-5">
                                    <textarea name="options[{{ $index }}][value]">{{ old("options.{$index}.value") }}</textarea>
                                </div>
                                <div class="col-2"></div>
                            </div>
                        @endfor
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
