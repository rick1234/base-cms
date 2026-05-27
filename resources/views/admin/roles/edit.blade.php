@extends('layouts.admin')

@php
    $isExisting = (bool) $role->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $role->name]) : __('Toevoegen');
    $selectedPermissionIds = collect(old('permissions', $selectedPermissionIds));
    $adminAccess = $permissionsByKey->get('admin.access');
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="role-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="role-form" name="saveAndStay" type="submit" value="1">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting)
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

            <form id="role-form" name="edit-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $role->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $role->id }}">

                <div class="main-section">
                    @include('admin.roles.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6">
                                <h2 class="title">{{ __('Rol') }}</h2>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="name">{{ __('Naam') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="name" name="name" type="text" value="{{ old('name', $role->name) }}" required>
                                        @include('admin.content.partials.field-error', ['field' => 'name'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="slug">{{ __('Slug') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="slug" name="slug" type="text" value="{{ old('slug', $role->slug) }}">
                                        @include('admin.content.partials.field-error', ['field' => 'slug'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="status">{{ __('Status') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="status" name="status">
                                            <option value="active" @selected(old('status', $role->status) === 'active')>{{ __('Actief') }}</option>
                                            <option value="inactive" @selected(old('status', $role->status) === 'inactive')>{{ __('Inactief') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <h2 class="title">{{ __('Omschrijving') }}</h2>
                                <textarea id="description" name="description">{{ old('description', $role->description) }}</textarea>

                                @if ($adminAccess)
                                    <h2 class="title">{{ __('Backend') }}</h2>
                                    <label class="role-permission-option role-permission-option-strong">
                                        <input type="checkbox" name="permissions[]" value="{{ $adminAccess->id }}" @checked($selectedPermissionIds->contains($adminAccess->id))>
                                        <span class="checkbox"></span>
                                        {{ __('Backend toegang') }}
                                    </label>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-section">
                    <h2 class="title">{{ __('Module rechten') }}</h2>

                    <div class="role-permission-module-list">
                        @foreach ($permissionGroups as $group)
                            <section class="role-permission-group">
                                <h3 class="sub-title">{{ $group['name'] }}</h3>

                                @foreach ($group['modules'] as $module)
                                    <details class="role-permission-module" open>
                                        <summary>{{ $module['name'] }}</summary>

                                        <div class="role-permission-grid">
                                            <div class="role-permission-column">
                                                <strong>{{ __('Module') }}</strong>
                                                @foreach ($module['actions'] as $action)
                                                    @php($permission = $permissionsByKey->get("admin.module.{$module['key']}.{$action['key']}"))
                                                    @if ($permission)
                                                        <label class="role-permission-option">
                                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($selectedPermissionIds->contains($permission->id))>
                                                            <span class="checkbox"></span>
                                                            {{ $action['label'] }}
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>

                                            <div class="role-permission-column">
                                                <strong>{{ __('Velden') }}</strong>
                                                @foreach ($module['fields'] as $field)
                                                    @php($permission = $permissionsByKey->get("admin.field.{$module['key']}.{$field['key']}"))
                                                    @if ($permission)
                                                        <label class="role-permission-option">
                                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($selectedPermissionIds->contains($permission->id))>
                                                            <span class="checkbox"></span>
                                                            {{ $field['label'] }}
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>

                                            <div class="role-permission-column">
                                                <strong>{{ __('Blokken') }}</strong>
                                                @forelse ($module['blocks'] as $block)
                                                    @php($permission = $permissionsByKey->get("admin.block.{$module['key']}.{$block['key']}"))
                                                    @if ($permission)
                                                        <label class="role-permission-option">
                                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($selectedPermissionIds->contains($permission->id))>
                                                            <span class="checkbox"></span>
                                                            {{ $block['label'] }}
                                                        </label>
                                                    @endif
                                                @empty
                                                    <span class="role-permission-empty">{{ __('Geen blokken') }}</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    </details>
                                @endforeach
                            </section>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
