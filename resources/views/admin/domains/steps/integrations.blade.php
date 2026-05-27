<section class="domain-wizard-panel" id="domain-step-integrations">
    <h2 class="title">{{ __('Integrations') }}</h2>

    @foreach (config('cms_domains.public_integrations') as $field => $label)
        <div class="form-item">
            <div class="form-item-label"><label for="public_integrations_{{ $field }}">{{ __($label) }}</label></div>
            <div class="form-item-input">
                <input id="public_integrations_{{ $field }}" name="public_integrations[{{ $field }}]" type="text" value="{{ old('public_integrations.'.$field, $publicIntegrations[$field] ?? '') }}">
                @error('public_integrations.'.$field)<span class="error">{{ $message }}</span>@enderror
            </div>
        </div>
    @endforeach

    @include('admin.domains.partials.step-actions')
</section>
