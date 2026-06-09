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
    $activeTab = $activeTab ?? 'identity';
@endphp

@section('title', $pageTitle)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                @if (! in_array($activeTab, ['sections', 'usp-sets', 'preview'], true))
                    <button class="btn btn-save" form="template-form" type="submit">
                        <x-admin.material-icon name="save" />
                        {{ __('Opslaan') }}
                    </button>
                @endif
                @if ($template->exists)
                    <form id="template-generate-form" method="post" action="{{ route('admin.templates.generate', $template) }}">
                        @csrf
                        <button class="btn" type="submit">
                            <x-admin.material-icon name="create_new_folder" />
                            {{ __('Generate folders') }}
                        </button>
                    </form>
                @endif
                <a class="btn" href="{{ route('admin.templates.index') }}">
                    <x-admin.material-icon name="arrow_back" />
                    {{ __('Terug') }}
                </a>
                @if ($deleteAction)
                    <form method="post" action="{{ $deleteAction }}">
                        @csrf
                        @method('delete')
                        <button class="btn btn-delete" type="submit">
                            <x-admin.material-icon name="delete" />
                            {{ __('Verwijderen') }}
                        </button>
                    </form>
                @endif
            </div>

            @include('admin.templates.partials.item-tabs', [
                'active' => $activeTab,
                'template' => $template,
            ])

            @if (! in_array($activeTab, ['sections', 'usp-sets', 'preview'], true))
                <form id="template-form" class="edit-form" method="post" action="{{ $action }}">
                    @csrf
                    @if ($method !== 'post')
                        @method($method)
                    @endif
                    <input type="hidden" name="active_tab" value="{{ $activeTab }}">

                    @if ($activeTab !== 'identity')
                        <input type="hidden" name="name" value="{{ old('name', $template->name) }}">
                        <input type="hidden" name="handle" value="{{ old('handle', $template->handle) }}">
                        <input type="hidden" name="description" value="{{ old('description', $template->description) }}">
                        <input type="hidden" name="is_active" value="{{ old('is_active', $template->is_active ? 1 : 0) }}">
                        <input type="hidden" name="sort_order" value="{{ old('sort_order', $template->sort_order ?? 0) }}">
                    @endif

                    <div class="main-section">
                        <div class="page-header">
                            <div class="page-header-title-container">
                                <div class="page-header-title-image-container">
                                    <x-admin.material-icon class="is-large" name="dashboard_customize" />
                                </div>
                                <strong>{{ $pageTitle }}</strong>
                            </div>
                        </div>

                        <span class="content-admin-screen-label">{{ $pageTitle }}</span>

                        @if ($activeTab === 'identity')
                            <div class="content-section">
                                <div class="grid">
                                    <div class="grid-row">
                                        <div class="col-6">
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
                                        </div>

                                        <div class="col-6">
                                            <div class="form-item">
                                                <div class="form-item-label">{{ __('Status') }}</div>
                                                <div class="form-item-input">
                                                    <input type="hidden" name="is_active" value="0">
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

                                            @if (! $template->exists)
                                                <h2 class="title">{{ __('Paths') }}</h2>
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif ($activeTab === 'settings')
                            <div class="content-section template-settings-tab">
                                <h2 class="title">{{ __('Default settings') }}</h2>

                                @foreach ([
                                    'primary_color' => 'Primary color',
                                    'secondary_color' => 'Secondary color',
                                    'tertiary_color' => 'Tertiary color',
                                    'accent_color' => 'Accent color',
                                    'surface_color' => 'Surface color',
                                    'canvas_color' => 'Canvas color',
                                    'light_color' => 'Light color',
                                    'grey_color' => 'Grey color',
                                    'dark_color' => 'Dark color',
                                    'ink_color' => 'Ink color',
                                    'muted_ink_color' => 'Muted ink color',
                                ] as $setting => $label)
                                    <div class="form-item">
                                        <div class="form-item-label"><label for="default_settings_{{ $setting }}">{{ __($label) }}</label></div>
                                        <div class="form-item-input">
                                            <input id="default_settings_{{ $setting }}" name="default_settings[{{ $setting }}]" type="text" value="{{ old('default_settings.'.$setting, $settings[$setting] ?? '') }}" data-coloris data-color-field inputmode="text" maxlength="7" pattern="^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$" autocomplete="off">
                                            @error('default_settings.'.$setting)<span class="error">{{ $message }}</span>@enderror
                                        </div>
                                    </div>
                                @endforeach

                                @foreach ([
                                    'base_font_family' => 'Base font family',
                                    'heading_font_family' => 'Heading font family',
                                    'button_radius' => 'Button radius',
                                    'content_width' => 'Content width',
                                    'wrapper_width' => 'Wrapper width',
                                    'logo_width' => 'Logo width',
                                    'logo_height' => 'Logo height',
                                    'hero_height' => 'Hero height',
                                ] as $setting => $label)
                                    <div class="form-item">
                                        <div class="form-item-label"><label for="default_settings_{{ $setting }}">{{ __($label) }}</label></div>
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
                            </div>
                        @elseif ($activeTab === 'paths')
                            <div class="content-section">
                                <h2 class="title">{{ __('Paths') }}</h2>
                                <p class="form-item-description">{{ __('These paths are generated from the technical name. Change them only for a deliberate custom template location.') }}</p>

                                @foreach ([
                                    'stylesheet_path' => 'Stylesheet path',
                                    'asset_path' => 'Asset path',
                                    'view_path' => 'View path',
                                ] as $field => $label)
                                    <div class="form-item">
                                        <div class="form-item-label"><label for="{{ $field }}">{{ __($label) }}</label></div>
                                        <div class="form-item-input">
                                            <input id="{{ $field }}" name="{{ $field }}" type="text" value="{{ old($field, $template->{$field}) }}">
                                            @error($field)<span class="error">{{ $message }}</span>@enderror
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </form>
            @elseif ($activeTab === 'sections')
                <div class="main-section">
                    <div class="page-header">
                        <div class="page-header-title-container">
                            <div class="page-header-title-image-container">
                                <x-admin.material-icon class="is-large" name="dashboard_customize" />
                            </div>
                            <strong>{{ __('Defined sections') }}</strong>
                        </div>
                    </div>

                    <livewire:admin.templates.template-section-editor :template="$template" :key="'template-section-editor-'.$template->id" />
                </div>
            @elseif ($activeTab === 'usp-sets')
                <div class="main-section">
                    <div class="page-header">
                        <div class="page-header-title-container">
                            <div class="page-header-title-image-container">
                                <x-admin.material-icon class="is-large" name="checklist" />
                            </div>
                            <strong>{{ __('USP sets') }}</strong>
                        </div>
                    </div>

                    <livewire:admin.templates.template-usp-set-editor :template="$template" :key="'template-usp-set-editor-'.$template->id" />
                </div>
            @else
                <div class="main-section">
                    <div class="page-header">
                        <div class="page-header-title-container">
                            <div class="page-header-title-image-container">
                                <x-admin.material-icon class="is-large" name="dashboard_customize" />
                            </div>
                            <strong>{{ __('Template preview') }}</strong>
                        </div>
                    </div>

                    <div class="template-preview-panel">
                        <x-admin.template-wireframe :template="$template" />

                        <div class="template-preview-actions">
                            <p class="form-item-description">{{ __('Regenerate frontend files when the template frontend has changed or missing folders need to be restored.') }}</p>
                            <button class="btn" form="template-generate-form" type="submit">
                                <x-admin.material-icon name="create_new_folder" />
                                {{ __('Regenerate frontend files') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
