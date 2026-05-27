<section class="domain-wizard-panel" id="domain-step-review">
    <h2 class="title">{{ __('Review') }}</h2>

    <dl class="domain-review-list">
        <dt>{{ __('Primary host') }}</dt>
        <dd>{{ old('host', $domain->host ?: __('Not set')) }}</dd>
        <dt>{{ __('Website title') }}</dt>
        <dd>{{ old('name', $domain->name ?: __('Not set')) }}</dd>
        <dt>{{ __('Template') }}</dt>
        <dd>{{ $domain->template?->name ?? __('Not selected') }}</dd>
        <dt>{{ __('Frontend languages') }}</dt>
        <dd>{{ implode(', ', array_map('strtoupper', $frontendLocales)) }}</dd>
        <dt>{{ __('SEO fallback') }}</dt>
        <dd>{{ old('default_meta_description', $domain->default_meta_description ?: __('Not set')) }}</dd>
    </dl>

    @include('admin.domains.partials.step-actions')
</section>
