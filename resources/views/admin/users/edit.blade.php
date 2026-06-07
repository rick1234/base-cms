@extends('layouts.admin')

@php
    $isExisting = (bool) $managedUser->id;
    $title = $isExisting ? __('Bewerk: :name', ['name' => $managedUser->displayName()]) : __('Toevoegen');
    $activeTab = $activeTab ?? 'profile';
    $linkedCategoryIds = old('categories', $isExisting ? $managedUser->categories->pluck('id')->all() : []);
    $linkedRoleIds = collect(old('roles', $isExisting ? $managedUser->effectiveRoles()->pluck('id')->all() : []));
    $formAction = route($routeNames['save'], array_filter(['id' => $managedUser->id]));
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                @if ($isExisting && $activeTab === 'access')
                    <form method="post" action="{{ route($routeNames['invitation'], ['user' => $managedUser, 'area' => 'frontend']) }}">
                        @csrf
                        <button class="btn" type="submit">
                            <x-admin.material-icon name="mail" />
                            {{ __('Send frontend invitation') }}
                        </button>
                    </form>
                    <form method="post" action="{{ route($routeNames['invitation'], ['user' => $managedUser, 'area' => 'backend']) }}">
                        @csrf
                        <button class="btn" type="submit">
                            <x-admin.material-icon name="admin_panel_settings" />
                            {{ __('Send backend invitation') }}
                        </button>
                    </form>
                @endif

                <button class="btn btn-save" form="user-form" type="submit">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="user-form" name="saveAndStay" type="submit" value="1">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting && ! auth()->user()?->is($managedUser))
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

            @include('admin.users.partials.item-tabs', [
                'active' => $activeTab,
                'managedUser' => $managedUser,
                'routeNames' => $routeNames,
            ])

            <form id="user-form" name="edit-form" enctype="multipart/form-data" method="post" action="{{ $formAction }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $managedUser->id }}">
                <input type="hidden" name="active_tab" value="{{ $activeTab }}">
                <input type="hidden" name="saveAndStay" value="0">

                @if ($activeTab === 'profile')
                    <input type="hidden" name="category_selection_submitted" value="1">

                    <div class="main-section">
                        @include('admin.users.partials.page-header', [
                            'title' => $title,
                            'section' => $pageName,
                        ])

                        <span class="content-admin-screen-label">{{ $pageName }}</span>

                        <div class="content-section">
                            <div class="grid">
                                <div class="grid-row">
                                    <div class="col-6">
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
                                                <label for="locale">{{ __('Taal') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <x-admin.locale-radio-group
                                                    name="locale"
                                                    :selected="old('locale', $managedUser->locale)"
                                                    :locales="$languages"
                                                    id-prefix="locale"
                                                />
                                                @include('admin.content.partials.field-error', ['field' => 'locale'])
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <h2 class="title">{{ __('Categorie') }}</h2>
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="main-section">
                        <h2 class="title">{{ __('Personal details') }}</h2>
                        <div class="grid">
                            <div class="grid-row">
                                <div class="col-6">
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
                                            <label for="country_code">{{ __('Land') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <select id="country_code" name="country_code">
                                                <option value="">{{ __('Kies een optie') }}</option>
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->iso2 }}" @selected(old('country_code', $managedUser->country_code) === $country->iso2)>
                                                        {{ $country->name }} ({{ $country->iso2 }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @include('admin.content.partials.field-error', ['field' => 'country_code'])
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
                @elseif ($activeTab === 'access')
                    <div class="main-section">
                        @include('admin.users.partials.page-header', [
                            'title' => __('Access'),
                            'section' => $pageName,
                        ])

                        <span class="content-admin-screen-label">{{ __('Access') }}</span>
                        <input type="hidden" name="is_active" value="0">
                        <input type="hidden" name="is_admin" value="0">

                        <div class="user-access-grid">
                            <section class="user-access-panel">
                                <div>
                                    <h2>{{ __('Frontend account') }}</h2>
                                    <p>{{ __('Allow this user to sign in to frontend account areas when the website uses member login.') }}</p>
                                </div>
                                <label class="toggle-switch">
                                    <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $managedUser->is_active ?? true))>
                                    <span class="toggle-switch-control"></span>
                                    <span>{{ __('Account active') }}</span>
                                </label>
                            </section>

                            <section class="user-access-panel">
                                <div>
                                    <h2>{{ __('Backend CMS access') }}</h2>
                                    <p>{{ __('Allow this user to enter the CMS. Use the Roles tab to decide what they can manage.') }}</p>
                                </div>
                                <label class="toggle-switch">
                                    <input id="is_admin" name="is_admin" type="checkbox" value="1" @checked(old('is_admin', $managedUser->is_admin))>
                                    <span class="toggle-switch-control"></span>
                                    <span>{{ __('CMS login enabled') }}</span>
                                </label>
                            </section>
                        </div>
                    </div>

                    <div class="main-section">
                        <h2 class="title">{{ __('Access period') }}</h2>
                        <div class="grid">
                            <div class="grid-row">
                                <div class="col-6">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="active_from">{{ __('Startdatum') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="active_from" name="active_from" type="date" value="{{ old('active_from', optional($managedUser->active_from)->format('Y-m-d')) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'active_from'])
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="active_until">{{ __('Einddatum') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="active_until" name="active_until" type="date" value="{{ old('active_until', optional($managedUser->active_until)->format('Y-m-d')) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'active_until'])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($activeTab === 'roles')
                    <input type="hidden" name="role_selection_submitted" value="1">

                    <div class="main-section">
                        @include('admin.users.partials.page-header', [
                            'title' => __('Roles'),
                            'section' => $pageName,
                        ])

                        <span class="content-admin-screen-label">{{ __('Roles') }}</span>

                        <div class="user-role-list is-tabbed">
                            @forelse ($roles as $role)
                                <label class="user-role-option">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($linkedRoleIds->contains($role->id))>
                                    <span class="checkbox"></span>
                                    <span>
                                        <strong>{{ $role->name }}</strong>
                                        @if ($role->slug)
                                            <small>{{ $role->slug }}</small>
                                        @endif
                                    </span>
                                </label>
                            @empty
                                <div class="attachment-message">
                                    <x-admin.material-icon name="info" />
                                    <em>{{ __('Maak eerst een rol aan via Rollen en rechten.') }}</em>
                                </div>
                            @endforelse
                        </div>
                        @include('admin.content.partials.field-error', ['field' => 'roles'])
                        <a class="btn" href="{{ route(request()->routeIs('cms.*') ? 'cms.roles.index' : 'admin.roles.index') }}">
                            <x-admin.material-icon name="edit" />
                            {{ __('Rollen beheren') }}
                        </a>
                    </div>
                @elseif ($activeTab === 'two-factor')
                    <div class="main-section">
                        @include('admin.users.partials.page-header', [
                            'title' => __('Two-factor authentication'),
                            'section' => $pageName,
                        ])

                        <span class="content-admin-screen-label">{{ __('Two-factor authentication') }}</span>

                        <div class="user-two-factor-panel">
                            <div class="user-two-factor-card">
                                <h2>{{ __('Authenticator app') }}</h2>
                                <p>{{ __('Use Google Authenticator, Microsoft Authenticator, 1Password, or another TOTP app.') }}</p>

                                @if ($managedUser->two_factor_secret)
                                    <dl class="user-two-factor-details">
                                        <div>
                                            <dt>{{ __('Status') }}</dt>
                                            <dd>{{ $managedUser->hasTwoFactorEnabled() ? __('Enabled') : __('Configured') }}</dd>
                                        </div>
                                        <div>
                                            <dt>{{ __('Secret key') }}</dt>
                                            <dd><code>{{ $managedUser->two_factor_secret }}</code></dd>
                                        </div>
                                        <div>
                                            <dt>{{ __('Setup link') }}</dt>
                                            <dd><code>{{ $twoFactorProvisioningUri }}</code></dd>
                                        </div>
                                    </dl>
                                @else
                                    <div class="attachment-message">
                                        <x-admin.material-icon name="info" />
                                        <em>{{ __('No two-factor key has been generated for this user yet.') }}</em>
                                    </div>
                                @endif

                                <div class="buttons-container">
                                    <button class="btn" form="generate-two-factor-form" type="submit">
                                        <x-admin.material-icon name="key" />
                                        {{ $managedUser->two_factor_secret ? __('Regenerate key') : __('Generate key') }}
                                    </button>
                                    @if ($managedUser->two_factor_secret)
                                        <button class="btn btn-remove" form="disable-two-factor-form" type="submit">
                                            <x-admin.material-icon name="lock_open" />
                                            {{ __('Disable 2FA') }}
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div class="user-two-factor-qr">
                                @if ($twoFactorQrSvg)
                                    <div class="user-two-factor-qr-code">
                                        {!! $twoFactorQrSvg !!}
                                    </div>
                                    <span>{{ __('Scan this QR code with the authenticator app.') }}</span>
                                @else
                                    <x-admin.material-icon name="qr_code_2" />
                                    <span>{{ __('Generate a key to create a shareable QR code.') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif ($activeTab === 'image')
                    <div class="main-section">
                        @include('admin.users.partials.page-header', [
                            'title' => __('Image'),
                            'section' => $pageName,
                        ])

                        <span class="content-admin-screen-label">{{ __('Image') }}</span>

                        <div class="user-image-uploader">
                            <div class="user-image-preview">
                                @if ($managedUser->image_path)
                                    <img src="{{ asset($managedUser->image_path) }}" alt="{{ __('Image') }}">
                                @else
                                    <x-admin.material-icon name="person" />
                                    <span>{{ __('No image selected') }}</span>
                                @endif
                            </div>
                            <div class="user-image-upload-panel">
                                <h2>{{ __('Profile image') }}</h2>
                                <p>{{ __('Upload a clear square image for this account. It will be used in admin profile surfaces when available.') }}</p>
                                <div class="form-item image-upload-container">
                                    <input class="image-file-input" id="image" type="file" name="image" accept="image/*">
                                    <label for="image">{{ __('Choose image') }}</label>
                                    <input class="upload-image-name" name="uploadImageName" placeholder="{{ __('Geen bestand geselecteerd') }}" type="text" disabled>
                                </div>
                                @include('admin.content.partials.field-error', ['field' => 'image'])
                                @if ($managedUser->image_path)
                                    <button class="btn delete-image-button" form="delete-user-image-form" type="submit">
                                        <x-admin.material-icon name="delete" />
                                        {{ __('Verwijder afbeelding') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </form>

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Gemaakt door') }}:</strong> {{ $managedUser->creator?->fullName() ?? '-' }}</span>
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

            @if ($isExisting)
                <form id="generate-two-factor-form" method="post" action="{{ route($routeNames['two-factor.generate'], $managedUser) }}">
                    @csrf
                </form>

                @if ($managedUser->two_factor_secret)
                    <form id="disable-two-factor-form" method="post" action="{{ route($routeNames['two-factor.disable'], $managedUser) }}">
                        @csrf
                        @method('delete')
                    </form>
                @endif
            @endif
        </div>
    </div>
@endsection
