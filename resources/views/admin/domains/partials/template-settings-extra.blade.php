@php
    $settingPrefix = $prefix ?? 'template_settings';
    $fieldIdPrefix = str_replace(['[', ']'], '_', $settingPrefix);
@endphp

<h3 class="sub-title">{{ __('Default layout') }}</h3>

@foreach ([
    'logo_path' => 'Logo path',
    'hero_image_path' => 'Hero image path',
] as $setting => $label)
    <div class="form-item">
        <div class="form-item-label"><label for="{{ $fieldIdPrefix }}_{{ $setting }}">{{ __($label) }}</label></div>
        <div class="form-item-input">
            <input id="{{ $fieldIdPrefix }}_{{ $setting }}" name="{{ $settingPrefix }}[{{ $setting }}]" type="text" value="{{ old($settingPrefix.'.'.$setting, $settings[$setting] ?? '') }}">
            @error($settingPrefix.'.'.$setting)<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>
@endforeach

@foreach ([
    'show_usp_bar' => 'Show USP bar',
    'sticky_header' => 'Sticky header',
    'show_hero' => 'Show hero',
    'show_footer_credit' => 'Show footer credit',
    'search_enabled' => 'Search enabled',
] as $setting => $label)
    <div class="form-item">
        <div class="form-item-label">{{ __($label) }}</div>
        <div class="form-item-input">
            <input name="{{ $settingPrefix }}[{{ $setting }}]" type="hidden" value="0">
            <label>
                <input name="{{ $settingPrefix }}[{{ $setting }}]" type="checkbox" value="1" @checked(filter_var(old($settingPrefix.'.'.$setting, $settings[$setting] ?? false), FILTER_VALIDATE_BOOLEAN))>
                {{ __('Enabled') }}
            </label>
            @error($settingPrefix.'.'.$setting)<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>
@endforeach

<div class="form-item">
    <div class="form-item-label"><label for="{{ $fieldIdPrefix }}_usp_items">{{ __('USP items') }}</label></div>
    <div class="form-item-input">
        <textarea id="{{ $fieldIdPrefix }}_usp_items" name="{{ $settingPrefix }}[usp_items]">{{ old($settingPrefix.'.usp_items', is_array($settings['usp_items'] ?? null) ? implode("\n", $settings['usp_items']) : ($settings['usp_items'] ?? '')) }}</textarea>
        @error($settingPrefix.'.usp_items')<span class="error">{{ $message }}</span>@enderror
    </div>
</div>

<h3 class="sub-title">{{ __('Default footer') }}</h3>

@foreach ([
    'footer_contact_title' => 'Contact title',
    'footer_contact_text' => 'Contact text',
    'footer_social_title' => 'Social title',
    'footer_social_text' => 'Social text',
    'footer_content_title' => 'Content title',
    'footer_credit_label' => 'Credit label',
    'footer_credit_url' => 'Credit URL',
] as $setting => $label)
    <div class="form-item">
        <div class="form-item-label"><label for="{{ $fieldIdPrefix }}_{{ $setting }}">{{ __($label) }}</label></div>
        <div class="form-item-input">
            <input id="{{ $fieldIdPrefix }}_{{ $setting }}" name="{{ $settingPrefix }}[{{ $setting }}]" type="{{ $setting === 'footer_credit_url' ? 'url' : 'text' }}" value="{{ old($settingPrefix.'.'.$setting, $settings[$setting] ?? '') }}">
            @error($settingPrefix.'.'.$setting)<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>
@endforeach

<div class="form-item">
    <div class="form-item-label"><label for="{{ $fieldIdPrefix }}_footer_content_text">{{ __('Content text') }}</label></div>
    <div class="form-item-input">
        <textarea id="{{ $fieldIdPrefix }}_footer_content_text" name="{{ $settingPrefix }}[footer_content_text]">{{ old($settingPrefix.'.footer_content_text', $settings['footer_content_text'] ?? '') }}</textarea>
        @error($settingPrefix.'.footer_content_text')<span class="error">{{ $message }}</span>@enderror
    </div>
</div>
