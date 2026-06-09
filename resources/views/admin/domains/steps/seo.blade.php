<section class="domain-tab-panel" id="domain-step-seo">
    <h2 class="title">{{ __('SEO') }}</h2>

    @php
        $seoFields = [
            'canonical_base_url' => __('Canonical base URL'),
            'default_meta_title' => __('Default meta title'),
            'default_meta_description' => __('Default meta description'),
            'default_og_title' => __('Default Open Graph title'),
            'default_og_description' => __('Default Open Graph description'),
            'default_og_image' => __('Default Open Graph image'),
        ];
        $localizedSeoFields = [
            'default_meta_title' => __('Fallback title'),
            'default_meta_description' => __('Fallback description'),
            'default_og_title' => __('Open Graph title'),
            'default_og_description' => __('Open Graph description'),
            'default_og_image' => __('Open Graph image'),
        ];
        $frontendSeoLocales = collect($frontendLocales ?? $domain->activeFrontendLocales())
            ->map(fn (string $locale): string => \Illuminate\Support\Str::of($locale)->replace('_', '-')->lower()->toString())
            ->unique()
            ->values();
    @endphp

    <div class="form-item">
        <div class="form-item-label"><label for="title_separator">{{ __('Title separator') }}</label></div>
        <div class="form-item-input">
            <input id="title_separator" name="title_separator" type="text" value="{{ old('title_separator', $domain->title_separator ?? '|') }}" required>
            @error('title_separator')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    @foreach ($seoFields as $field => $label)
        <div class="form-item">
            <div class="form-item-label"><label for="{{ $field }}">{{ $label }}</label></div>
            <div class="form-item-input">
                @if (str_contains($field, 'description'))
                    <textarea id="{{ $field }}" name="{{ $field }}">{{ old($field, $domain->{$field}) }}</textarea>
                @elseif ($field === 'canonical_base_url')
                    <input id="{{ $field }}" name="{{ $field }}" type="url" value="{{ old($field, $domain->{$field}) }}">
                @else
                    <input id="{{ $field }}" name="{{ $field }}" type="text" value="{{ old($field, $domain->{$field}) }}">
                @endif
                @error($field)<span class="error">{{ $message }}</span>@enderror
            </div>
        </div>
    @endforeach

    @if ($frontendSeoLocales->isNotEmpty())
        <section class="domain-tab-panel" aria-labelledby="domain-localized-seo-title">
            <h3 class="title" id="domain-localized-seo-title">{{ __('Language-specific SEO fallbacks') }}</h3>

            @foreach ($frontendSeoLocales as $locale)
                <section class="domain-localized-seo" aria-labelledby="domain-localized-seo-{{ $locale }}-title">
                    <h4 id="domain-localized-seo-{{ $locale }}-title">{{ strtoupper($locale) }}</h4>

                    @foreach ($localizedSeoFields as $field => $label)
                        @php
                            $fieldId = "settings_seo_locales_{$locale}_{$field}";
                            $fieldName = "settings[seo][locales][{$locale}][{$field}]";
                            $fieldValue = old("settings.seo.locales.{$locale}.{$field}", data_get($domain->settings ?? [], "seo.locales.{$locale}.{$field}"));
                        @endphp

                        <div class="form-item">
                            <div class="form-item-label"><label for="{{ $fieldId }}">{{ $label }}</label></div>
                            <div class="form-item-input">
                                @if (str_contains($field, 'description'))
                                    <textarea id="{{ $fieldId }}" name="{{ $fieldName }}">{{ $fieldValue }}</textarea>
                                @else
                                    <input id="{{ $fieldId }}" name="{{ $fieldName }}" type="text" value="{{ $fieldValue }}">
                                @endif
                                @error("settings.seo.locales.{$locale}.{$field}")<span class="error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    @endforeach
                </section>
            @endforeach
        </section>
    @endif

    <div class="form-item">
        <div class="form-item-label"><label for="robots">{{ __('Robots') }}</label></div>
        <div class="form-item-input">
            <select id="robots" name="robots">
                <option value="">{{ __('Index normally') }}</option>
                @foreach (['index,follow', 'noindex,follow', 'noindex,nofollow'] as $robots)
                    <option value="{{ $robots }}" @selected(old('robots', $domain->robots) === $robots)>{{ $robots }}</option>
                @endforeach
            </select>
            @error('robots')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    @include('admin.domains.partials.step-actions')
</section>
