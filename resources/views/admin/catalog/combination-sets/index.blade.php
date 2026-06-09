@extends('layouts.admin')

@section('title', __('Combination sets'))

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
                    'title' => __('Combination sets'),
                    'section' => __('Combinaties overzicht'),
                ])

                <div class="overview-container catalog-combination-sets-overview">
                    <div class="overview-row header">
                        <div class="overview-item id">Id</div>
                        <div class="overview-item title">{{ __('Naam') }}</div>
                        <div class="overview-item category">{{ __('Producten') }}</div>
                        <div class="overview-item status">{{ __('Status') }}</div>
                        <div class="overview-item options">{{ __('Opties') }}</div>
                    </div>

                    <form method="get" action="{{ route($routeNames['index']) }}">
                        <div class="overview-row filters">
                            <div class="overview-item id">
                                <input name="id" type="text" value="{{ request('id') }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item title">
                                <input name="name" type="text" value="{{ request('name') }}">
                                <x-admin.material-icon name="search" class="search-icon" />
                            </div>
                            <div class="overview-item category"></div>
                            <div class="overview-item status">
                                <select name="status">
                                    <option value="">{{ __('Selecteer') }}</option>
                                    <option value="active" @selected(request('status') === 'active')>{{ __('Actief') }}</option>
                                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactief') }}</option>
                                </select>
                            </div>
                            <div class="overview-item options">
                                <button type="submit" title="{{ __('Zoeken') }}">
                                    <x-admin.material-icon name="search" />
                                </button>
                            </div>
                        </div>
                    </form>

                    @forelse ($sets as $set)
                        <div class="overview-row">
                            <div class="overview-item id">{{ $set->id }}</div>
                            <div class="overview-item title">{{ $set->name }}</div>
                            <div class="overview-item category">{{ $set->products_count }}</div>
                            <div class="overview-item status">{{ $set->status === 'active' ? __('Actief') : __('Inactief') }}</div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $set->id]) }}" title="{{ __('Bewerken') }}">
                                    <x-admin.material-icon name="edit" />
                                </a>
                                <form method="post" action="{{ route($routeNames['destroy'], ['id' => $set->id]) }}">
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
                            <div class="overview-item title">{{ __('Geen combinaties gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $sets])
            </div>
        </div>
    </div>
@endsection
