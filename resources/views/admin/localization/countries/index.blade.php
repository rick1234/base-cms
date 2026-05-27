@extends('layouts.admin')

@section('title', __('Countries'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <a class="btn btn-add" href="{{ route($routeNames['create']) }}">
                    <span class="flaticon-add-plus-button"></span>
                    {{ __('Toevoegen') }}
                </a>
                <a class="btn" href="{{ route($routeNames['languages']) }}">
                    <span class="flaticon-earth-grid-select-language-button"></span>
                    {{ __('Website languages') }}
                </a>
                <form method="post" action="{{ route($routeNames['sync']) }}">
                    @csrf
                    <button class="btn" type="submit">
                        <span class="flaticon-refresh-button"></span>
                        {{ __('Refresh package data') }}
                    </button>
                </form>
            </div>

            <div class="main-section">
                @include('admin.localization.partials.page-header', [
                    'title' => __('Countries'),
                    'section' => __('Country overview'),
                ])

                <span class="content-admin-screen-label">{{ __('Country overview') }}</span>

                <div class="overview-container countries-overview-container">
                    <div class="overview-row header">
                        <div class="overview-item id">
                            Id
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'id', 'sorttype' => 'asc'])) }}">
                                <span class="flaticon-downwards-arrow-key"></span>
                            </a>
                        </div>
                        <div class="overview-item name">
                            {{ __('Naam') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'name', 'sorttype' => 'asc'])) }}">
                                <span class="flaticon-downwards-arrow-key"></span>
                            </a>
                        </div>
                        <div class="overview-item code">{{ __('Code') }}</div>
                        <div class="overview-item currency">{{ __('Currency') }}</div>
                        <div class="overview-item enabled">{{ __('Website') }}</div>
                        <div class="overview-item status">{{ __('Actief') }}</div>
                        <div class="overview-item options">{{ __('Opties') }}</div>
                    </div>

                    <form method="get" action="{{ route($routeNames['index']) }}">
                        <div class="overview-row filters">
                            <div class="overview-item id">
                                <input name="id" type="text" value="{{ request('id') }}">
                                <span class="flaticon-searching-magnifying-glass search-icon"></span>
                            </div>
                            <div class="overview-item name">
                                <input name="name" type="text" value="{{ request('name', request('naam')) }}">
                                <span class="flaticon-searching-magnifying-glass search-icon"></span>
                            </div>
                            <div class="overview-item code">
                                <input name="iso2" type="text" value="{{ request('iso2', request('code')) }}">
                                <span class="flaticon-searching-magnifying-glass search-icon"></span>
                            </div>
                            <div class="overview-item currency"></div>
                            <div class="overview-item enabled">
                                <select name="enabled">
                                    <option value="">{{ __('Alle') }}</option>
                                    <option value="1" @selected(request('enabled') === '1')>{{ __('Enabled') }}</option>
                                    <option value="0" @selected(request('enabled') === '0')>{{ __('Disabled') }}</option>
                                </select>
                            </div>
                            <div class="overview-item status">
                                <select name="status">
                                    <option value="">{{ __('Alle') }}</option>
                                    <option value="active" @selected(in_array(request('status', request('active')), ['active', '1'], true))>{{ __('Actief') }}</option>
                                    <option value="inactive" @selected(in_array(request('status', request('active')), ['inactive', '0', '2'], true))>{{ __('Inactief') }}</option>
                                </select>
                            </div>
                            <div class="overview-item options">
                                <button type="submit" title="{{ __('Zoeken') }}">
                                    <span class="flaticon-searching-magnifying-glass"></span>
                                </button>
                            </div>
                        </div>
                    </form>

                    @forelse ($countries as $country)
                        <div class="overview-row">
                            <div class="overview-item id">{{ $country->id }}</div>
                            <div class="overview-item name">
                                <a href="{{ route($routeNames['edit'], ['id' => $country->id]) }}">{{ $country->name }}</a>
                                @if ($country->iso3)
                                    <small>{{ $country->iso3 }}</small>
                                @endif
                            </div>
                            <div class="overview-item code">{{ $country->iso2 ?: '-' }}</div>
                            <div class="overview-item currency">{{ $country->currency_code ?: '-' }}</div>
                            <div class="overview-item enabled">
                                <span class="{{ $country->is_enabled ? 'active-item' : 'inactive-item' }}"></span>
                            </div>
                            <div class="overview-item status">
                                <span class="{{ $country->status === 'active' ? 'active-item' : 'inactive-item' }}"></span>
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $country->id]) }}" title="{{ __('Bewerken') }}">
                                    <span class="flaticon-create-new-pencil-button"></span>
                                </a>
                                <form method="post" action="{{ route($routeNames['destroy'], $country) }}">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" title="{{ __('Verwijderen') }}">
                                        <span class="flaticon-rubbish-bin-delete-button"></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="overview-row">
                            <div class="overview-item name">{{ __('Geen landen gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $countries])
            </div>
        </div>
    </div>
@endsection
