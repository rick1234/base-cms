@extends('layouts.admin')

@section('title', __('Action Codes'))

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
                @include('admin.catalog.partials.toolbar-links')
            </div>

            <div class="main-section">
                @include('admin.catalog.partials.page-header', [
                    'title' => __('Action Codes'),
                    'section' => __('Actiecodes overzicht'),
                ])

                <div class="overview-container actioncode-overview-container">
                    <div class="overview-row header">
                        <div class="overview-item id">Id</div>
                        <div class="overview-item title">{{ __('Naam') }}</div>
                        <div class="overview-item code">{{ __('Code') }}</div>
                        <div class="overview-item price">{{ __('Korting') }}</div>
                        <div class="overview-item status">{{ __('Status') }}</div>
                        <div class="overview-item options">{{ __('Opties') }}</div>
                    </div>

                    <form method="get" action="{{ route($routeNames['index']) }}">
                        <div class="overview-row filters">
                            <div class="overview-item id">
                                <input name="id" type="text" value="{{ request('id') }}">
                            </div>
                            <div class="overview-item title">
                                <input name="name" type="text" value="{{ request('name', request('naam')) }}">
                            </div>
                            <div class="overview-item code">
                                <input name="code" type="text" value="{{ request('code') }}">
                            </div>
                            <div class="overview-item price"></div>
                            <div class="overview-item status">
                                <select name="status">
                                    <option value="">{{ __('Selecteer') }}</option>
                                    <option value="active" @selected(request('status') === 'active' || request('status') === '1')>{{ __('Actief') }}</option>
                                    <option value="inactive" @selected(request('status') === 'inactive' || request('status') === '0')>{{ __('Inactief') }}</option>
                                </select>
                            </div>
                            <div class="overview-item options">
                                <button type="submit" title="{{ __('Zoeken') }}">
                                    <x-admin.material-icon name="search" />
                                </button>
                            </div>
                        </div>
                    </form>

                    @forelse ($coupons as $coupon)
                        <div class="overview-row">
                            <div class="overview-item id">{{ $coupon->id }}</div>
                            <div class="overview-item title">{{ $coupon->name }}</div>
                            <div class="overview-item code">{{ $coupon->code }}</div>
                            <div class="overview-item price">{{ $coupon->percentage_discount }}%</div>
                            <div class="overview-item status">
                                <span class="{{ $coupon->is_active ? 'active-item' : 'inactive-item' }}"></span>
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $coupon->id]) }}" title="{{ __('Bewerken') }}">
                                    <x-admin.material-icon name="edit" />
                                </a>
                                <form method="post" action="{{ route($routeNames['destroy'], $coupon) }}">
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
                            <div class="overview-item title">{{ __('Geen actiecodes gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $coupons])
            </div>
        </div>
    </div>
@endsection
