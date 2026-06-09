<section class="domain-tab-panel" id="domain-step-identity">
    <h2 class="title">{{ __('Domain') }}</h2>

    <div class="form-item">
        <div class="form-item-label"><label for="host">{{ __('Primary host') }}</label></div>
        <div class="form-item-input">
            <input id="host" name="host" type="text" value="{{ old('host', $domain->host) }}" required>
            @error('host')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    <div class="form-item">
        <div class="form-item-label"><label for="local_domain">{{ __('Local development domain') }}</label></div>
        <div class="form-item-input">
            <input id="local_domain" type="text" value="{{ $localDomain }}" readonly>
            <p class="form-item-description">{{ __('This follows the Laravel Herd folder-name .test convention and is kept available for frontend domain testing.') }}</p>
        </div>
    </div>

    <div class="form-item">
        <div class="form-item-label"><label for="aliases_text">{{ __('Aliases') }}</label></div>
        <div class="form-item-input">
            <textarea id="aliases_text" name="aliases_text">{{ $aliasesText }}</textarea>
            @error('aliases_text')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    <div class="form-item">
        <div class="form-item-label"><label for="name">{{ __('Website title') }}</label></div>
        <div class="form-item-input">
            <input id="name" name="name" type="text" value="{{ old('name', $domain->name) }}" required>
            @error('name')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    <div class="form-item">
        <div class="form-item-label"><label for="company_name">{{ __('Company name') }}</label></div>
        <div class="form-item-input">
            <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $domain->company_name) }}">
            @error('company_name')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    <div class="form-item">
        <div class="form-item-label"><label for="default_locale">{{ __('Default language') }}</label></div>
        <div class="form-item-input">
            <select id="default_locale" name="default_locale">
                <option value="">{{ __('Use CMS default') }}</option>
                @foreach ($languages as $language)
                    <option value="{{ $language->code }}" @selected(old('default_locale', $domain->default_locale) === $language->code)>
                        {{ $language->label() }}
                    </option>
                @endforeach
            </select>
            @error('default_locale')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    <div class="form-item">
        <div class="form-item-label">{{ __('Status') }}</div>
        <div class="form-item-input">
            <label><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $domain->exists ? $domain->is_active : true))> {{ __('Active') }}</label>
            <label><input name="is_development" type="checkbox" value="1" @checked(old('is_development', $domain->is_development))> {{ __('Development preview domain') }}</label>
        </div>
    </div>

    <div class="form-item">
        <div class="form-item-label"><label for="sort_order">{{ __('Sort order') }}</label></div>
        <div class="form-item-input">
            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $domain->sort_order ?? 0) }}" required>
            @error('sort_order')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    @include('admin.domains.partials.step-actions')
</section>
