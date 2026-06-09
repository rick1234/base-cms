@extends('layouts.admin')

@php
    $isExisting = (bool) $fakeRecord['id'];
    $title = $isExisting ? __('Bewerk: :title', ['title' => $fakeRecord['title']]) : __('Toevoegen');
    $backUrl = route($routeNames['index']);
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                @if ($isExisting && $activeTab === 'info')
                    <button class="btn btn-save btn-save-blocks" type="button">
                        <x-admin.material-icon name="save" />
                        {{ __('Save blocks') }}
                    </button>
                @endif
                <button class="btn btn-save" form="base-conventions-form" type="button">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="base-conventions-form" type="button">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting)
                    <button class="btn btn-duplicate" type="button">
                        <x-admin.material-icon name="content_copy" />
                        {{ __('Dupliceren') }}
                    </button>
                    <button class="btn btn-remove" type="button">
                        <x-admin.material-icon name="delete" />
                        {{ __('Verwijderen') }}
                    </button>
                @endif
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
                </a>
            </div>

            @include('admin.base-module-conventions.partials.tabs', [
                'active' => $activeTab,
                'fakeRecord' => $fakeRecord,
                'routeNames' => $routeNames,
            ])

            @if ($activeTab === 'info')
                <form id="base-conventions-form" name="edit-form" method="post" action="#" accept-charset="UTF-8">
                    @csrf
                    <input type="hidden" name="id" value="{{ $fakeRecord['id'] }}">

                    <div class="main-section">
                        @include('admin.content.partials.page-header', [
                            'title' => $title,
                            'section' => __('Base module conventions'),
                            'icon' => 'dashboard_customize',
                        ])

                        <span class="content-admin-screen-label">{{ __('Base module conventions') }}</span>

                        <div class="content-section">
                            <div class="grid">
                                <div class="grid-row">
                                    <div class="col-6">
                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="title">{{ __('Titel') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input id="title" name="title" type="text" value="{{ old('title', $fakeRecord['title']) }}" required>
                                                @include('admin.content.partials.field-error', ['field' => 'title'])
                                            </div>
                                        </div>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="slug">{{ __('URL') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <div class="url-input-group">
                                                    <span class="url-input-base">https://base-cms.test/</span>
                                                    <input id="slug" name="slug" type="text" value="{{ old('slug', $fakeRecord['slug']) }}">
                                                </div>
                                                @include('admin.content.partials.field-error', ['field' => 'slug'])
                                            </div>
                                        </div>

                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="locale">{{ __('Taal') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <x-admin.locale-radio-group name="locale" :selected="old('locale', $fakeRecord['locale'])" id-prefix="locale" />
                                                @include('admin.content.partials.field-error', ['field' => 'locale'])
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <h2 class="title">{{ __('Categorie') }}</h2>
                                        <div class="categories-tree base-conventions-category-tree">
                                            <ul>
                                                @foreach ($categories as $category)
                                                    <li>
                                                        <label>
                                                            <input type="checkbox" @checked($category['name'] === $fakeRecord['category'])>
                                                            <span class="checkbox"></span>
                                                            {{ $category['name'] }}
                                                        </label>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        @include('admin.content.partials.field-error', ['field' => 'categories'])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="main-section">
                        <div class="grid">
                            <h3 class="sub-title">{{ __('Configuration') }}</h3>
                            <div class="grid-row">
                                <div class="col-6">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="layout_set">{{ __('Layout set') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <select id="layout_set" name="layout_set">
                                                @foreach ($layoutSets as $layoutSet)
                                                    <option value="{{ $layoutSet['key'] }}">{{ $layoutSet['key'] }} - {{ $layoutSet['label'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="status">{{ __('Status') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <select id="status" name="status">
                                                <option value="published" @selected($fakeRecord['status'] === 'published')>{{ __('Online') }}</option>
                                                <option value="draft" @selected($fakeRecord['status'] === 'draft')>{{ __('Offline') }}</option>
                                                <option value="archived">{{ __('Archived') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                @if ($isExisting)
                    <div class="main-section">
                        <h2 class="title">{{ __('Blokken toevoegen') }}</h2>

                        <div class="base-conventions-builder">
                            <div class="base-conventions-block-list">
                                @foreach ($fakeBlocks as $block)
                                    <article class="base-conventions-block">
                                        <span class="base-conventions-block-icon">
                                            <x-admin.material-icon :name="$block['icon']" />
                                        </span>
                                        <div>
                                            <h3>{{ $block['title'] }}</h3>
                                            <p>{{ __('Width') }}: {{ $block['width'] }}</p>
                                        </div>
                                        <button type="button" title="{{ __('Move item') }}">
                                            <x-admin.material-icon name="open_with" />
                                        </button>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @elseif ($activeTab === 'form')
                <div class="main-section">
                    @include('admin.content.partials.page-header', [
                        'title' => __('Formulier'),
                        'section' => __('Base module conventions'),
                        'icon' => 'dynamic_form',
                    ])

                    <span class="content-admin-screen-label">{{ __('Formulier') }}</span>

                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="form_id">{{ __('Kies een formulier') }}</label>
                        </div>
                        <div class="form-item-input">
                            <select id="form_id" name="form_id">
                                <option>{{ __('Default contact form') }}</option>
                                <option>{{ __('Signup form') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            @elseif ($activeTab === 'seo')
                <div class="main-section">
                    @include('admin.content.partials.page-header', [
                        'title' => __('SEO settings'),
                        'section' => __('Base module conventions'),
                        'icon' => 'search',
                    ])

                    <span class="content-admin-screen-label">{{ __('SEO settings') }}</span>

                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="meta_description">{{ __('Meta omschrijving') }}</label>
                        </div>
                        <div class="form-item-input">
                            <textarea id="meta_description" name="meta_description">{{ __('Reference admin module layout for future CMS modules.') }}</textarea>
                            @include('admin.content.partials.field-error', ['field' => 'meta_description'])
                        </div>
                    </div>
                </div>
            @endif

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Gemaakt door') }}:</strong> {{ $fakeRecord['createdBy'] }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ $fakeRecord['createdAt'] }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
