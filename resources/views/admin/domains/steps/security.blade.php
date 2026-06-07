<div class="domain-step-grid">
    <section class="domain-step-card">
        <h2>{{ __('Two-factor authentication') }}</h2>
        <p>{{ __('Require CMS users on this domain to enter an authenticator code after their password.') }}</p>

        <input type="hidden" name="settings[security][backend_two_factor_required]" value="0">
        <label class="toggle-switch">
            <input
                name="settings[security][backend_two_factor_required]"
                type="checkbox"
                value="1"
                @checked(old('settings.security.backend_two_factor_required', $domain->requiresTwoFactorForBackend()))
            >
            <span class="toggle-switch-control"></span>
            <span>{{ __('Require 2FA for CMS login') }}</span>
        </label>
    </section>
</div>

@include('admin.domains.partials.step-actions')
