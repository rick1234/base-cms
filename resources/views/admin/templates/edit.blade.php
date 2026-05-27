@extends('layouts.admin')

@php
    $pageTitle = $template->exists ? __('Edit template') : __('Create template');
    $settings = [
        ...config('cms_domains.default_template_settings'),
        ...($template->default_settings ?? []),
    ];
    $pathHandle = old('handle', $template->handle ?: '{technical-name}');
    $pathPreview = [
        'stylesheet_path' => "resources/scss/site/templates/{$pathHandle}/_index.scss",
        'view_path' => "resources/views/site/templates/{$pathHandle}",
        'asset_path' => "public/site/templates/{$pathHandle}",
    ];
@endphp

@section('title', $pageTitle)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container">
                <div class="buttons-container align-right">
                    <button class="btn btn-add" form="template-form" type="submit">
                        <span class="flaticon-save-file-option"></span>
                        {{ __('Opslaan') }}
                    </button>
                    @if ($template->exists)
                        <form method="post" action="{{ route('admin.templates.generate', $template) }}">
                            @csrf
                            <button class="btn" type="submit">
                                <x-admin.material-icon name="create_new_folder" />
                                {{ __('Generate folders') }}
                            </button>
                        </form>
                    @endif
                    <a class="btn" href="{{ route('admin.templates.index') }}">
                        <span class="flaticon-back-arrow"></span>
                        {{ __('Terug') }}
                    </a>
                    @if ($deleteAction)
                        <form method="post" action="{{ $deleteAction }}">
                            @csrf
                            @method('delete')
                            <button class="btn btn-delete" type="submit">
                                <span class="flaticon-delete-button"></span>
                                {{ __('Verwijderen') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="main-section">
                <div class="page-header">
                    <div class="page-header-title-container">
                        <div class="page-header-title-image-container">
                            <x-admin.material-icon class="is-large" name="dashboard_customize" />
                        </div>
                        <strong>{{ $pageTitle }}</strong>
                    </div>
                </div>

                <div class="breadcrumbs">
                    <a href="{{ route('admin.dashboard') }}">{{ __('Home') }}</a> &rsaquo;
                    <a href="{{ route('admin.templates.index') }}">{{ __('Website Templates') }}</a> &rsaquo;
                    {{ $pageTitle }}
                </div>

                <form id="template-form" class="edit-form" method="post" action="{{ $action }}">
                    @csrf
                    @if ($method !== 'post')
                        @method($method)
                    @endif

                    <h2 class="title">{{ __('Template identity') }}</h2>

                    <div class="form-item">
                        <div class="form-item-label"><label for="name">{{ __('Name') }}</label></div>
                        <div class="form-item-input">
                            <input id="name" name="name" type="text" value="{{ old('name', $template->name) }}" required>
                            @error('name')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label"><label for="handle">{{ __('Technical name') }}</label></div>
                        <div class="form-item-input">
                            <input id="handle" name="handle" type="text" value="{{ old('handle', $template->handle) }}" required>
                            <p class="form-item-description">{{ __('Used for the template folder names. Example: default, clean-corporate, campaign-nl.') }}</p>
                            @error('handle')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label"><label for="description">{{ __('Description') }}</label></div>
                        <div class="form-item-input">
                            <textarea id="description" name="description">{{ old('description', $template->description) }}</textarea>
                            @error('description')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label">{{ __('Status') }}</div>
                        <div class="form-item-input">
                            <label><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $template->exists ? $template->is_active : true))> {{ __('Active') }}</label>
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label"><label for="sort_order">{{ __('Sort order') }}</label></div>
                        <div class="form-item-input">
                            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $template->sort_order ?? 0) }}" required>
                            @error('sort_order')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <h2 class="title">{{ __('Paths') }}</h2>

                    @if ($template->exists)
                        <p class="form-item-description">{{ __('These paths are generated from the technical name. Change them only for a deliberate custom template location.') }}</p>

                        @foreach (['stylesheet_path', 'asset_path', 'view_path'] as $field)
                            <div class="form-item">
                                <div class="form-item-label"><label for="{{ $field }}">{{ str($field)->replace('_', ' ')->title() }}</label></div>
                                <div class="form-item-input">
                                    <input id="{{ $field }}" name="{{ $field }}" type="text" value="{{ old($field, $template->{$field}) }}">
                                    @error($field)<span class="error">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="form-item-description">{{ __('These paths will be set automatically after creation from the technical name.') }}</p>
                        <dl class="domain-review-list">
                            <dt>{{ __('Stylesheet path') }}</dt>
                            <dd>{{ $pathPreview['stylesheet_path'] }}</dd>
                            <dt>{{ __('View path') }}</dt>
                            <dd>{{ $pathPreview['view_path'] }}</dd>
                            <dt>{{ __('Asset path') }}</dt>
                            <dd>{{ $pathPreview['asset_path'] }}</dd>
                        </dl>
                    @endif

                    <h2 class="title">{{ __('Default settings') }}</h2>

                    @foreach (['primary_color', 'secondary_color', 'tertiary_color', 'accent_color', 'surface_color', 'canvas_color', 'light_color', 'grey_color', 'dark_color', 'ink_color', 'muted_ink_color'] as $setting)
                        <div class="form-item">
                            <div class="form-item-label"><label for="default_settings_{{ $setting }}">{{ str($setting)->replace('_', ' ')->title() }}</label></div>
                            <div class="form-item-input">
                                <input id="default_settings_{{ $setting }}" name="default_settings[{{ $setting }}]" type="text" value="{{ old('default_settings.'.$setting, $settings[$setting] ?? '') }}" data-coloris data-color-field inputmode="text" maxlength="7" pattern="^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$" autocomplete="off">
                                @error('default_settings.'.$setting)<span class="error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    @endforeach

                    @foreach (['base_font_family', 'heading_font_family', 'button_radius', 'content_width', 'wrapper_width', 'logo_width', 'logo_height', 'hero_height'] as $setting)
                        <div class="form-item">
                            <div class="form-item-label"><label for="default_settings_{{ $setting }}">{{ str($setting)->replace('_', ' ')->title() }}</label></div>
                            <div class="form-item-input">
                                <input id="default_settings_{{ $setting }}" name="default_settings[{{ $setting }}]" type="text" value="{{ old('default_settings.'.$setting, $settings[$setting] ?? '') }}">
                                @error('default_settings.'.$setting)<span class="error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    @endforeach

                    @foreach ([
                        'base_font_google_url' => 'Base Google font link',
                        'heading_font_google_url' => 'Heading Google font link',
                    ] as $setting => $label)
                        @php
                            $previewKind = $setting === 'base_font_google_url' ? 'base' : 'heading';
                        @endphp
                        <div class="form-item">
                            <div class="form-item-label"><label for="default_settings_{{ $setting }}">{{ __($label) }}</label></div>
                            <div class="form-item-input">
                                <input id="default_settings_{{ $setting }}" name="default_settings[{{ $setting }}]" type="url" value="{{ old('default_settings.'.$setting, $settings[$setting] ?? '') }}" placeholder="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&amp;display=swap" data-google-font-preview-input="{{ $previewKind }}">
                                <p class="form-item-description">{{ __('Paste the Google Fonts stylesheet link. The family name is inferred for this font slot.') }}</p>
                                <div
                                    class="template-font-preview"
                                    data-google-font-preview-card="{{ $previewKind }}"
                                    data-google-font-preview-fallback="{{ $previewKind === 'heading' ? 'serif' : 'sans-serif' }}"
                                    data-google-font-preview-empty="{{ __('Add a Google Fonts link to preview this font.') }}"
                                    data-google-font-preview-invalid="{{ __('Enter a valid Google Fonts stylesheet URL.') }}"
                                >
                                    <span class="template-font-preview-label" data-google-font-preview-label>{{ __('Font preview') }}</span>
                                    <p class="template-font-preview-sample" data-google-font-preview-sample>
                                        {{ $previewKind === 'heading' ? __('Heading: Build clear, fast websites') : __('Body: The quick brown fox jumps over the lazy dog.') }}
                                    </p>
                                </div>
                                @error('default_settings.'.$setting)<span class="error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    @endforeach

                    <div class="form-item">
                        <div class="form-item-label"><label for="default_settings_button_style">{{ __('Button style') }}</label></div>
                        <div class="form-item-input">
                            <select id="default_settings_button_style" name="default_settings[button_style]">
                                @foreach (config('cms_domains.button_styles') as $value => $label)
                                    <option value="{{ $value }}" @selected(old('default_settings.button_style', $settings['button_style'] ?? 'solid') === $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label"><label for="default_settings_title_style">{{ __('Title style') }}</label></div>
                        <div class="form-item-input">
                            <select id="default_settings_title_style" name="default_settings[title_style]">
                                @foreach (['strong' => 'Strong', 'quiet' => 'Quiet', 'editorial' => 'Editorial'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('default_settings.title_style', $settings['title_style'] ?? 'strong') === $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label"><label for="default_settings_social_placement">{{ __('Social placement') }}</label></div>
                        <div class="form-item-input">
                            <select id="default_settings_social_placement" name="default_settings[social_placement]">
                                @foreach (config('cms_domains.placement_options') as $value => $label)
                                    <option value="{{ $value }}" @selected(old('default_settings.social_placement', $settings['social_placement'] ?? 'footer') === $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label"><label for="default_settings_contact_form_placement">{{ __('Contact form placement') }}</label></div>
                        <div class="form-item-input">
                            <select id="default_settings_contact_form_placement" name="default_settings[contact_form_placement]">
                                @foreach (config('cms_domains.placement_options') as $value => $label)
                                    <option value="{{ $value }}" @selected(old('default_settings.contact_form_placement', $settings['contact_form_placement'] ?? 'footer') === $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @include('admin.domains.partials.template-settings-extra', [
                        'prefix' => 'default_settings',
                        'settings' => $settings,
                    ])
                </form>
            </div>
        </div>
    </div>
@endsection
