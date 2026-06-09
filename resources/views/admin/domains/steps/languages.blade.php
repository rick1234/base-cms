<section class="domain-tab-panel" id="domain-step-languages">
    <h2 class="title">{{ __('Languages') }}</h2>

    <div class="form-item">
        <div class="form-item-label">{{ __('Frontend languages') }}</div>
        <div class="form-item-input">
            @foreach ($languages as $language)
                <label>
                    <input name="active_frontend_locales[]" type="checkbox" value="{{ $language->code }}" @checked(in_array($language->code, $frontendLocales, true))>
                    <x-admin.language-flag :locale="$language->code" :label="$language->label()" decorative />
                    {{ $language->label() }}
                </label>
            @endforeach
            @error('active_frontend_locales')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    <div class="form-item">
        <div class="form-item-label">{{ __('Backend languages') }}</div>
        <div class="form-item-input">
            @foreach ($languages as $language)
                <label>
                    <input name="active_backend_locales[]" type="checkbox" value="{{ $language->code }}" @checked(in_array($language->code, $backendLocales, true))>
                    <x-admin.language-flag :locale="$language->code" :label="$language->label()" decorative />
                    {{ $language->label() }}
                </label>
            @endforeach
            @error('active_backend_locales')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    <div class="form-item">
        <div class="form-item-label"><label for="language_search">{{ __('Add website language') }}</label></div>
        <div class="form-item-input">
            <input
                id="language_search"
                name="language_search"
                type="search"
                value="{{ old('language_search') }}"
                list="domain-language-options"
                placeholder="{{ __('Search by name or code') }}"
                autocomplete="off"
            >
            <datalist id="domain-language-options">
                @foreach ($languageOptions as $language)
                    <option value="{{ $language->name }}">{{ $language->label() }}</option>
                @endforeach
            </datalist>
            <p class="form-item-description">{{ __('Saving this step activates the matching language in website languages.') }}</p>
            @error('language_search')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    <div class="form-item">
        <div class="form-item-label">{{ __('Use added language') }}</div>
        <div class="form-item-input">
            <label><input name="language_add_to_frontend" type="checkbox" value="1" @checked(old('language_add_to_frontend', true))> {{ __('Frontend') }}</label>
            <label><input name="language_add_to_backend" type="checkbox" value="1" @checked(old('language_add_to_backend', true))> {{ __('Backend') }}</label>
        </div>
    </div>

    @include('admin.domains.partials.step-actions')
</section>
