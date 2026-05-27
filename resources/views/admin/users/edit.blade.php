@extends('layouts.admin')

@php
    $isExisting = (bool) $managedUser->id;
    $title = $isExisting ? __('Bewerk: :name', ['name' => $managedUser->displayName()]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $managedUser->categories->pluck('id')->all() : []);
    $linkedRoleIds = collect(old('roles', $isExisting ? $managedUser->effectiveRoles()->pluck('id')->all() : []));
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="user-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="user-form" name="saveAndStay" type="submit" value="1">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting && ! auth()->user()?->is($managedUser))
                    <form method="post" action="{{ $deleteAction }}">
                        @csrf
                        @method('delete')
                        <button class="btn btn-remove" type="submit">
                            <span class="flaticon-close-button"></span>
                            {{ __('Verwijderen') }}
                        </button>
                    </form>
                @endif
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            <form id="user-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $managedUser->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $managedUser->id }}">
                <input type="hidden" name="saveAndStay" value="0">
                <input type="hidden" name="is_active" value="0">
                <input type="hidden" name="is_admin" value="0">

                <div class="main-section">
                    @include('admin.users.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="content-section">
                        <h2 class="title">{{ __('Algemeen') }}</h2>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="username">{{ __('Gebruikersnaam') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="username" name="username" type="text" value="{{ old('username', $managedUser->username) }}" autocomplete="off">
                                @include('admin.content.partials.field-error', ['field' => 'username'])
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="name">{{ __('Naam') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="name" name="name" type="text" value="{{ old('name', $managedUser->name) }}" required>
                                @include('admin.content.partials.field-error', ['field' => 'name'])
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="email">{{ __('E-mail') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="email" name="email" type="email" value="{{ old('email', $managedUser->email) }}" required>
                                @include('admin.content.partials.field-error', ['field' => 'email'])
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="password">{{ __('Wachtwoord') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="password" name="password" type="password" autocomplete="new-password" @required(! $isExisting)>
                                @include('admin.content.partials.field-error', ['field' => 'password'])
                            </div>
                            <p class="form-item-info">
                                <span class="admin-symbol admin-symbol-info" aria-hidden="true"></span>
                                {{ $isExisting ? __('Laat leeg om het huidige wachtwoord te behouden.') : __('Gebruik minimaal 8 tekens.') }}
                            </p>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="locale">{{ __('Standaard taalcode') }}</label>
                            </div>
                            <div class="form-item-input">
                                <select id="locale" name="locale">
                                    <option value="">{{ __('Kies een optie') }}</option>
                                    @foreach ($languages as $language)
                                        <option value="{{ $language->code }}" @selected(old('locale', $managedUser->locale) === $language->code)>
                                            {{ $language->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @include('admin.content.partials.field-error', ['field' => 'locale'])
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-section">
                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6">
                                <h2 class="title">{{ __('Opties') }}</h2>
                                <h3 class="sub-title">{{ __('Periode') }}</h3>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="active_from">{{ __('Startdatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="active_from" name="active_from" type="date" value="{{ old('active_from', optional($managedUser->active_from)->format('Y-m-d')) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'active_from'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="active_until">{{ __('Einddatum') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="active_until" name="active_until" type="date" value="{{ old('active_until', optional($managedUser->active_until)->format('Y-m-d')) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'active_until'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="is_active">{{ __('Actief') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <label>
                                            <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $managedUser->is_active ?? true))>
                                            <span class="checkbox"></span>
                                            {{ __('Ja') }}
                                        </label>
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="is_admin">{{ __('Backend toegang') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <label>
                                            <input id="is_admin" name="is_admin" type="checkbox" value="1" @checked(old('is_admin', $managedUser->is_admin))>
                                            <span class="checkbox"></span>
                                            {{ __('Admin') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <h2 class="title">{{ __('Selecteer een categorie') }}</h2>
                                <div class="categories-tree">
                                    @include('admin.users.partials.category-tree', [
                                        'categoriesByParent' => $categories->groupBy(fn ($category) => $category->parent_id ?: 0),
                                        'parentId' => 0,
                                        'linkedIds' => $linkedCategoryIds,
                                        'mode' => 'select',
                                        'routeNames' => $routeNames,
                                    ])
                                </div>
                                @include('admin.content.partials.field-error', ['field' => 'categories'])

                                <h2 class="title">{{ __('Gebruikers rollen') }}</h2>
                                <div class="user-role-list">
                                    @forelse ($roles as $role)
                                        <label class="user-role-option">
                                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($linkedRoleIds->contains($role->id))>
                                            <span class="checkbox"></span>
                                            {{ $role->name }}
                                        </label>
                                    @empty
                                        <div class="attachment-message">
                                            <span class="flaticon-rounded-info-button"></span>
                                            <em>{{ __('Maak eerst een rol aan via Rollen en rechten.') }}</em>
                                        </div>
                                    @endforelse
                                </div>
                                @include('admin.content.partials.field-error', ['field' => 'roles'])
                                <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.roles.index' : 'admin.roles.index') }}">
                                    <span class="flaticon-create-new-pencil-button"></span>
                                    {{ __('Rollen beheren') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-section">
                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6">
                                <h2 class="title">{{ __('Afbeelding') }}</h2>
                                <div class="form-item image-upload-container">
                                    <input class="image-file-input" id="image" type="file" name="image" accept="image/*">
                                    <label for="image">{{ __('Bladeren') }}</label>
                                    <input class="upload-image-name" name="uploadImageName" placeholder="{{ __('Geen bestand geselecteerd') }}" type="text" disabled>
                                </div>
                                @include('admin.content.partials.field-error', ['field' => 'image'])
                            </div>
                            <div class="col-6">
                                @if ($managedUser->image_path)
                                    <img src="{{ asset($managedUser->image_path) }}" alt="{{ __('Afbeelding') }}" class="user-image">
                                    <button class="btn delete-image-button" form="delete-user-image-form" type="submit">
                                        <span class="flaticon-close-button"></span>
                                        {{ __('Verwijder afbeelding') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-section">
                    <h2 class="title">{{ __('Gegevens') }}</h2>

                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6">
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="salutation">{{ __('Aanhef') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="salutation" name="salutation" type="text" value="{{ old('salutation', $managedUser->salutation) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="first_name">{{ __('Voornaam') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $managedUser->first_name) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="middle_name">{{ __('Tussenvoegsel') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name', $managedUser->middle_name) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="last_name">{{ __('Achternaam') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $managedUser->last_name) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="gender">{{ __('Geslacht') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="gender" name="gender">
                                            <option value="">{{ __('Kies een optie') }}</option>
                                            <option value="M" @selected(old('gender', $managedUser->gender) === 'M')>{{ __('Man') }}</option>
                                            <option value="V" @selected(old('gender', $managedUser->gender) === 'V')>{{ __('Vrouw') }}</option>
                                            <option value="X" @selected(old('gender', $managedUser->gender) === 'X')>{{ __('Anders') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="company_name">{{ __('Bedrijfsnaam') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $managedUser->company_name) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="street">{{ __('Straat') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="street" name="street" type="text" value="{{ old('street', $managedUser->street) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="house_number">{{ __('Huisnummer') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="house_number" name="house_number" type="text" value="{{ old('house_number', $managedUser->house_number) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="house_number_addition">{{ __('Huisnummer toevoeging') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="house_number_addition" name="house_number_addition" type="text" value="{{ old('house_number_addition', $managedUser->house_number_addition) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="postal_code">{{ __('Postcode') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $managedUser->postal_code) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="city">{{ __('Plaats') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="city" name="city" type="text" value="{{ old('city', $managedUser->city) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="country_code">{{ __('Landcode') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="country_code" name="country_code" type="text" value="{{ old('country_code', $managedUser->country_code) }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="phone">{{ __('Telefoon') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="phone" name="phone" type="text" value="{{ old('phone', $managedUser->phone) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Auteur') }}:</strong> {{ $managedUser->creator?->displayName() ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($managedUser->created_at)->format('d-m-Y H:i') }}</span>
                    @if ($managedUser->updated_at)
                        <span><strong>{{ __('Aangepast op') }}:</strong> {{ $managedUser->updated_at->format('d-m-Y H:i') }}</span>
                    @endif
                </div>
            @endif

            @if ($isExisting && $managedUser->image_path)
                <form id="delete-user-image-form" method="post" action="{{ route($routeNames['image.delete']) }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $managedUser->id }}">
                </form>
            @endif
        </div>
    </div>
@endsection
