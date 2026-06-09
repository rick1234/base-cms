<section class="domain-tab-panel" id="domain-step-favicon">
    <h2 class="title">{{ __('Favicon') }}</h2>

    @php
        $faviconPreviewUrl = $domain->faviconUrl('source') ?: $domain->faviconUrl('svg') ?: $domain->faviconUrl('apple_touch_icon');
    @endphp

    @if ($faviconPreviewUrl)
        <div class="form-item">
            <div class="form-item-label">{{ __('Current favicon') }}</div>
            <div class="form-item-input">
                <img class="domain-favicon-preview" src="{{ $faviconPreviewUrl }}" alt="">
            </div>
        </div>
    @endif

    <div class="form-item">
        <div class="form-item-label"><label for="favicon_logo">{{ __('Logo for favicon') }}</label></div>
        <div class="form-item-input">
            <input id="favicon_logo" name="favicon_logo" type="file" accept="image/svg+xml,image/png,image/jpeg,image/webp">
            <p class="form-item-description">{{ __('Upload a logo as SVG, PNG, JPG, or WebP. The CMS generates the favicon files automatically.') }}</p>
            @error('favicon_logo')<span class="error">{{ $message }}</span>@enderror
            @error('favicon_svg')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    @include('admin.domains.partials.step-actions')
</section>
