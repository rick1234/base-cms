@extends('layouts.admin')

@section('title', __('Location opening hours'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="location-opening-hours-form" type="submit">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
                </button>
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
                </a>
            </div>

            <form id="location-opening-hours-form" method="post" action="{{ route($routeNames['opening-hours.save'], ['id' => $location->id]) }}">
                @csrf
                <input type="hidden" name="id" value="{{ $location->id }}">

                <div class="main-section">
                    @include('admin.locations.partials.page-header', [
                        'title' => $location->name,
                        'section' => $pageName,
                    ])

                    @include('admin.locations.partials.tabs', [
                        'location' => $location,
                        'routeNames' => $routeNames,
                        'activeTab' => 'opening-hours',
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <h2 class="title">{{ __('Openingstijden') }}</h2>
                    <div class="location-opening-hours-list">
                        @foreach ($dayNames as $day => $label)
                            @php $openingHour = $openingHours->get((string) $day); @endphp
                            <div class="location-opening-hours-row">
                                <strong>{{ $label }}</strong>
                                <label>
                                    <span>{{ __('Open') }}</span>
                                    <input name="opening_hours[{{ $day }}][opens_at]" type="time" value="{{ old('opening_hours.'.$day.'.opens_at', substr((string) $openingHour?->opens_at, 0, 5)) }}">
                                </label>
                                <label>
                                    <span>{{ __('Gesloten') }}</span>
                                    <input name="opening_hours[{{ $day }}][closes_at]" type="time" value="{{ old('opening_hours.'.$day.'.closes_at', substr((string) $openingHour?->closes_at, 0, 5)) }}">
                                </label>
                                <label class="location-closed-option">
                                    <input type="hidden" name="opening_hours[{{ $day }}][is_closed]" value="0">
                                    <input name="opening_hours[{{ $day }}][is_closed]" type="checkbox" value="1" @checked(old('opening_hours.'.$day.'.is_closed', $openingHour?->is_closed))>
                                    <span class="checkbox"></span>
                                    {{ __('Gesloten') }}
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <h2 class="title">{{ __('Speciale openingstijden') }}</h2>
                    <div class="location-special-hours-list">
                        @foreach ($location->specialOpeningHours as $index => $specialOpeningHour)
                            <div class="location-special-hours-row">
                                <input type="hidden" name="special_opening_hours[{{ $index }}][id]" value="{{ $specialOpeningHour->id }}">
                                <input name="special_opening_hours[{{ $index }}][title]" type="text" value="{{ old('special_opening_hours.'.$index.'.title', $specialOpeningHour->title) }}" placeholder="{{ __('Titel') }}">
                                <input name="special_opening_hours[{{ $index }}][date]" type="date" value="{{ old('special_opening_hours.'.$index.'.date', optional($specialOpeningHour->date)->format('Y-m-d')) }}">
                                <input name="special_opening_hours[{{ $index }}][opens_at]" type="time" value="{{ old('special_opening_hours.'.$index.'.opens_at', substr((string) $specialOpeningHour->opens_at, 0, 5)) }}">
                                <input name="special_opening_hours[{{ $index }}][closes_at]" type="time" value="{{ old('special_opening_hours.'.$index.'.closes_at', substr((string) $specialOpeningHour->closes_at, 0, 5)) }}">
                                <label class="location-closed-option">
                                    <input type="hidden" name="special_opening_hours[{{ $index }}][is_closed]" value="0">
                                    <input name="special_opening_hours[{{ $index }}][is_closed]" type="checkbox" value="1" @checked(old('special_opening_hours.'.$index.'.is_closed', $specialOpeningHour->is_closed))>
                                    <span class="checkbox"></span>
                                    {{ __('Gesloten') }}
                                </label>
                                <label class="location-delete-option">
                                    <input type="checkbox" name="special_opening_hours[{{ $index }}][delete]" value="1">
                                    <span class="checkbox"></span>
                                    {{ __('Verwijderen') }}
                                </label>
                            </div>
                        @endforeach

                        @php $newIndex = $location->specialOpeningHours->count(); @endphp
                        <div class="location-special-hours-row is-new">
                            <input name="special_opening_hours[{{ $newIndex }}][title]" type="text" value="{{ old('special_opening_hours.'.$newIndex.'.title') }}" placeholder="{{ __('Titel') }}">
                            <input name="special_opening_hours[{{ $newIndex }}][date]" type="date" value="{{ old('special_opening_hours.'.$newIndex.'.date') }}">
                            <input name="special_opening_hours[{{ $newIndex }}][opens_at]" type="time" value="{{ old('special_opening_hours.'.$newIndex.'.opens_at') }}">
                            <input name="special_opening_hours[{{ $newIndex }}][closes_at]" type="time" value="{{ old('special_opening_hours.'.$newIndex.'.closes_at') }}">
                            <label class="location-closed-option">
                                <input type="hidden" name="special_opening_hours[{{ $newIndex }}][is_closed]" value="0">
                                <input name="special_opening_hours[{{ $newIndex }}][is_closed]" type="checkbox" value="1" @checked(old('special_opening_hours.'.$newIndex.'.is_closed'))>
                                <span class="checkbox"></span>
                                {{ __('Gesloten') }}
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
