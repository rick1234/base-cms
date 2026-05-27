@extends('layouts.admin')

@section('title', __('Roles and Permissions'))

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
            </div>

            <div class="main-section">
                @include('admin.roles.partials.page-header', [
                    'title' => __('Roles and Permissions'),
                    'section' => __('Role overview'),
                ])

                <span class="content-admin-screen-label">{{ __('Role overview') }}</span>

                <div class="overview-container roles-overview-container">
                    <div class="overview-row header">
                        <div class="overview-item id">Id</div>
                        <div class="overview-item name">{{ __('Naam') }}</div>
                        <div class="overview-item permissions">{{ __('Rechten') }}</div>
                        <div class="overview-item users">{{ __('Gebruikers') }}</div>
                        <div class="overview-item status">{{ __('Status') }}</div>
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
                            <div class="overview-item permissions"></div>
                            <div class="overview-item users"></div>
                            <div class="overview-item status">
                                <select name="status">
                                    <option value="">{{ __('Alle') }}</option>
                                    <option value="active" @selected(request('status') === 'active')>{{ __('Actief') }}</option>
                                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactief') }}</option>
                                </select>
                            </div>
                            <div class="overview-item options">
                                <button type="submit" title="{{ __('Zoeken') }}">
                                    <span class="flaticon-searching-magnifying-glass"></span>
                                </button>
                            </div>
                        </div>
                    </form>

                    @forelse ($roles as $role)
                        <div class="overview-row">
                            <div class="overview-item id">{{ $role->id }}</div>
                            <div class="overview-item name">
                                <a href="{{ route($routeNames['edit'], ['id' => $role->id]) }}">{{ $role->name }}</a>
                                @if ($role->description)
                                    <small>{{ $role->description }}</small>
                                @endif
                            </div>
                            <div class="overview-item permissions">{{ $role->permissions_count }}</div>
                            <div class="overview-item users">{{ $role->users_count }}</div>
                            <div class="overview-item status">
                                <span class="{{ $role->status === 'active' ? 'active-item' : 'inactive-item' }}"></span>
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $role->id]) }}" title="{{ __('Bewerken') }}">
                                    <span class="flaticon-create-new-pencil-button"></span>
                                </a>
                                @if ($role->users_count === 0)
                                    <form method="post" action="{{ route($routeNames['destroy'], $role) }}">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" title="{{ __('Verwijderen') }}">
                                            <span class="flaticon-rubbish-bin-delete-button"></span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="overview-row">
                            <div class="overview-item name">{{ __('Geen rollen gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $roles])
            </div>
        </div>
    </div>
@endsection
