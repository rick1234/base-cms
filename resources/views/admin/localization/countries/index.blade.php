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
                    <x-admin.material-icon name="add" />
                    {{ __('Toevoegen') }}
                </a>
                <a class="btn" href="{{ route($routeNames['languages']) }}">
                    <x-admin.material-icon name="language" />
                    {{ __('Website languages') }}
                </a>
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
                                <x-admin.material-icon name="keyboard_arrow_down" />
                            </a>
                        </div>
                        <div class="overview-item name">
                            {{ __('Naam') }}
                            <a href="{{ route($routeNames['index'], array_merge(request()->except(['sort', 'sorttype']), ['sort' => 'name', 'sorttype' => 'asc'])) }}">
                                <x-admin.material-icon name="keyboard_arrow_down" />
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
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item name">
                                <input name="name" type="text" value="{{ request('name', request('naam')) }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item code">
                                <input name="iso2" type="text" value="{{ request('iso2', request('code')) }}">
                                <x-admin.material-icon name="search" class="search-icon" />
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
                                    <x-admin.material-icon name="search" />
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
                                    <x-admin.material-icon name="edit" />
                                </a>
                                <form method="post" action="{{ route($routeNames['destroy'], $country) }}">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" title="{{ __('Verwijderen') }}">
                                        <x-admin.material-icon name="delete" />
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
