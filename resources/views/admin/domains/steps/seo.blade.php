<section class="domain-wizard-panel" id="domain-step-seo">
    <h2 class="title">{{ __('SEO') }}</h2>

    <div class="form-item">
        <div class="form-item-label"><label for="title_separator">{{ __('Title separator') }}</label></div>
        <div class="form-item-input">
            <input id="title_separator" name="title_separator" type="text" value="{{ old('title_separator', $domain->title_separator ?? '|') }}" required>
            @error('title_separator')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    @foreach (['canonical_base_url', 'default_meta_title', 'default_meta_description', 'default_og_title', 'default_og_description', 'default_og_image'] as $field)
        <div class="form-item">
            <div class="form-item-label"><label for="{{ $field }}">{{ str($field)->replace('_', ' ')->title() }}</label></div>
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
