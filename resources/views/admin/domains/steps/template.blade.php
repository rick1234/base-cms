<section class="domain-tab-panel" id="domain-step-template">
    <h2 class="title">{{ __('Template') }}</h2>

    <div class="form-item">
        <div class="form-item-label"><label for="website_template_id">{{ __('Website template') }}</label></div>
        <div class="form-item-input">
            <select id="website_template_id" name="website_template_id">
                <option value="">{{ __('No template') }}</option>
                @foreach ($templates as $template)
                    <option value="{{ $template->id }}" @selected((int) old('website_template_id', $domain->website_template_id) === $template->id)>
                        {{ $template->name }}
                    </option>
                @endforeach
            </select>
            @error('website_template_id')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

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
            <div class="form-item-label"><label for="template_settings_{{ $setting }}">{{ __($label) }}</label></div>
            <div class="form-item-input">
                <input id="template_settings_{{ $setting }}" name="template_settings[{{ $setting }}]" type="text" value="{{ old('template_settings.'.$setting, $settings[$setting] ?? '') }}" data-coloris data-color-field inputmode="text" maxlength="7" pattern="^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$" autocomplete="off">
                @error('template_settings.'.$setting)<span class="error">{{ $message }}</span>@enderror
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
            <div class="form-item-label"><label for="template_settings_{{ $setting }}">{{ __($label) }}</label></div>
            <div class="form-item-input">
                <input id="template_settings_{{ $setting }}" name="template_settings[{{ $setting }}]" type="text" value="{{ old('template_settings.'.$setting, $settings[$setting] ?? '') }}">
                @error('template_settings.'.$setting)<span class="error">{{ $message }}</span>@enderror
            </div>
        </div>
    @endforeach

    @foreach ([
        'base_font_google_url' => 'Base Google font link',
        'heading_font_google_url' => 'Heading Google font link',
    ] as $setting => $label)
        <div class="form-item">
            <div class="form-item-label"><label for="template_settings_{{ $setting }}">{{ __($label) }}</label></div>
            <div class="form-item-input">
                <input id="template_settings_{{ $setting }}" name="template_settings[{{ $setting }}]" type="url" value="{{ old('template_settings.'.$setting, $settings[$setting] ?? '') }}" placeholder="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&amp;display=swap">
                <p class="form-item-description">{{ __('Paste the Google Fonts stylesheet link. The family name is inferred for this font slot.') }}</p>
                @error('template_settings.'.$setting)<span class="error">{{ $message }}</span>@enderror
            </div>
        </div>
    @endforeach

    <div class="form-item">
        <div class="form-item-label"><label for="template_settings_button_style">{{ __('Button style') }}</label></div>
        <div class="form-item-input">
            <select id="template_settings_button_style" name="template_settings[button_style]">
                @foreach (config('cms_domains.button_styles') as $value => $label)
                    <option value="{{ $value }}" @selected(old('template_settings.button_style', $settings['button_style'] ?? 'solid') === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="form-item">
        <div class="form-item-label"><label for="template_settings_title_style">{{ __('Title style') }}</label></div>
        <div class="form-item-input">
            <select id="template_settings_title_style" name="template_settings[title_style]">
                @foreach (['strong' => 'Strong', 'quiet' => 'Quiet', 'editorial' => 'Editorial'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('template_settings.title_style', $settings['title_style'] ?? 'strong') === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="form-item">
        <div class="form-item-label"><label for="template_settings_social_placement">{{ __('Social placement') }}</label></div>
        <div class="form-item-input">
            <select id="template_settings_social_placement" name="template_settings[social_placement]">
                @foreach (config('cms_domains.placement_options') as $value => $label)
                    <option value="{{ $value }}" @selected(old('template_settings.social_placement', $settings['social_placement'] ?? 'footer') === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="form-item">
        <div class="form-item-label"><label for="template_settings_contact_form_placement">{{ __('Contact form placement') }}</label></div>
        <div class="form-item-input">
            <select id="template_settings_contact_form_placement" name="template_settings[contact_form_placement]">
                @foreach (config('cms_domains.placement_options') as $value => $label)
                    <option value="{{ $value }}" @selected(old('template_settings.contact_form_placement', $settings['contact_form_placement'] ?? 'footer') === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @include('admin.domains.partials.template-settings-extra', [
        'prefix' => 'template_settings',
        'settings' => $settings,
    ])

    @include('admin.domains.partials.step-actions')
</section>
