<?php

namespace App\Http\Requests\Admin\Domains;

use App\Models\Cms\Domain;
use App\Support\Domains\DomainLanguageActivator;
use App\Support\Domains\DomainWizard;
use App\Support\Domains\GoogleFontUrl;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $domain = $this->route('domain');

        $rules = [
            '_domain_step' => ['nullable', Rule::in(DomainWizard::keys())],
            '_next_step' => ['nullable', Rule::in(DomainWizard::keys())],
            'host' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i',
                Rule::unique('domains', 'host')->ignore($domain instanceof Domain ? $domain->id : null),
            ],
            'aliases_text' => ['nullable', 'string', 'max:5000'],
            'aliases' => ['array'],
            'aliases.*' => ['string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'website_template_id' => ['nullable', 'integer', 'exists:website_templates,id'],
            'default_locale' => ['nullable', 'string', 'max:16'],
            'active_frontend_locales' => ['required', 'array', 'min:1'],
            'active_frontend_locales.*' => ['string', 'max:16'],
            'active_backend_locales' => ['required', 'array', 'min:1'],
            'active_backend_locales.*' => ['string', 'max:16'],
            'language_search' => ['nullable', 'string', 'max:255', $this->languageSearchRule()],
            'language_add_to_frontend' => ['nullable', 'boolean'],
            'language_add_to_backend' => ['nullable', 'boolean'],
            'title_separator' => ['required', 'string', 'max:16'],
            'canonical_base_url' => ['nullable', 'url', 'max:255'],
            'default_meta_title' => ['nullable', 'string', 'max:255'],
            'default_meta_description' => ['nullable', 'string', 'max:500'],
            'default_og_title' => ['nullable', 'string', 'max:255'],
            'default_og_description' => ['nullable', 'string', 'max:500'],
            'default_og_image' => ['nullable', 'string', 'max:255'],
            'robots' => ['nullable', Rule::in(['index,follow', 'noindex,follow', 'noindex,nofollow'])],
            'template_settings' => ['array'],
            'template_settings.primary_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'template_settings.secondary_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'template_settings.tertiary_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'template_settings.accent_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'template_settings.surface_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'template_settings.canvas_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'template_settings.light_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'template_settings.grey_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'template_settings.dark_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'template_settings.ink_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'template_settings.muted_ink_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'template_settings.base_font_family' => ['nullable', 'string', 'max:255'],
            'template_settings.heading_font_family' => ['nullable', 'string', 'max:255'],
            'template_settings.base_font_google_url' => ['nullable', 'url', 'max:2048', $this->googleFontUrlRule()],
            'template_settings.heading_font_google_url' => ['nullable', 'url', 'max:2048', $this->googleFontUrlRule()],
            'template_settings.title_style' => ['nullable', Rule::in(['strong', 'quiet', 'editorial'])],
            'template_settings.button_style' => ['nullable', Rule::in(array_keys(config('cms_domains.button_styles')))],
            'template_settings.button_radius' => ['nullable', 'regex:/^\d+(?:\.\d+)?(?:px|rem|em|%)$/'],
            'template_settings.content_width' => ['nullable', 'regex:/^\d+(?:\.\d+)?(?:px|rem|em|%)$/'],
            'template_settings.wrapper_width' => ['nullable', 'regex:/^\d+(?:\.\d+)?(?:px|rem|em|%)$/'],
            'template_settings.logo_width' => ['nullable', 'regex:/^\d+(?:\.\d+)?(?:px|rem|em|%)$/'],
            'template_settings.logo_height' => ['nullable', 'regex:/^\d+(?:\.\d+)?(?:px|rem|em|%)$/'],
            'template_settings.hero_height' => ['nullable', 'regex:/^\d+(?:\.\d+)?(?:px|rem|em|%)$/'],
            'template_settings.logo_path' => ['nullable', 'string', 'max:255'],
            'template_settings.hero_image_path' => ['nullable', 'string', 'max:255'],
            'template_settings.show_usp_bar' => ['nullable', 'boolean'],
            'template_settings.sticky_header' => ['nullable', 'boolean'],
            'template_settings.show_hero' => ['nullable', 'boolean'],
            'template_settings.show_footer_credit' => ['nullable', 'boolean'],
            'template_settings.search_enabled' => ['nullable', 'boolean'],
            'template_settings.usp_items' => ['nullable', 'string', 'max:2000'],
            'template_settings.footer_contact_title' => ['nullable', 'string', 'max:120'],
            'template_settings.footer_contact_text' => ['nullable', 'string', 'max:255'],
            'template_settings.footer_social_title' => ['nullable', 'string', 'max:120'],
            'template_settings.footer_social_text' => ['nullable', 'string', 'max:500'],
            'template_settings.footer_content_title' => ['nullable', 'string', 'max:120'],
            'template_settings.footer_content_text' => ['nullable', 'string', 'max:1000'],
            'template_settings.footer_credit_label' => ['nullable', 'string', 'max:120'],
            'template_settings.footer_credit_url' => ['nullable', 'url', 'max:255'],
            'template_settings.social_placement' => ['nullable', Rule::in(array_keys(config('cms_domains.placement_options')))],
            'template_settings.contact_form_placement' => ['nullable', Rule::in(array_keys(config('cms_domains.placement_options')))],
            'social_links' => ['array'],
            'social_links.*.platform' => ['nullable', 'string', 'max:80'],
            'social_links.*.label' => ['nullable', 'string', 'max:120'],
            'social_links.*.icon' => ['nullable', 'string', 'max:120'],
            'social_links.*.url' => ['nullable', 'url', 'max:255'],
            'contact_form_id' => ['nullable', 'integer', 'exists:forms,id'],
            'public_integrations' => ['array'],
            'public_integrations.google_analytics_measurement_id' => ['nullable', 'string', 'max:40'],
            'public_integrations.google_tag_manager_container_id' => ['nullable', 'string', 'max:40'],
            'public_integrations.matomo_url' => ['nullable', 'url', 'max:255'],
            'public_integrations.matomo_site_id' => ['nullable', 'string', 'max:80'],
            'public_integrations.meta_pixel_id' => ['nullable', 'string', 'max:80'],
            'settings' => ['array'],
            'settings.security' => ['array'],
            'settings.security.backend_two_factor_required' => ['sometimes', 'boolean'],
            'favicon_logo' => ['nullable', 'file', 'max:8192', 'mimetypes:image/svg+xml,text/plain,application/xml,text/xml,image/png,image/jpeg,image/webp'],
            'favicon_svg' => ['nullable', 'file', 'max:8192', 'mimetypes:image/svg+xml,text/plain,application/xml,text/xml,image/png,image/jpeg,image/webp'],
            'is_active' => ['sometimes', 'boolean'],
            'is_development' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];

        $step = $this->input('_domain_step');

        if (! is_string($step) || $step === '') {
            return $rules;
        }

        return [
            '_domain_step' => ['required', Rule::in(DomainWizard::keys())],
            '_next_step' => ['nullable', Rule::in(DomainWizard::keys())],
            ...$this->onlyRulesForStep($rules, DomainWizard::normalize($step)),
        ];
    }

    protected function prepareForValidation(): void
    {
        $aliases = collect(preg_split('/\R+/', (string) $this->input('aliases_text')) ?: [])
            ->map(fn (string $host): string => Domain::normalizeHost($host))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'host' => Domain::normalizeHost((string) $this->input('host')),
            'aliases' => $aliases,
            'is_active' => $this->boolean('is_active'),
            'is_development' => $this->boolean('is_development'),
            'language_add_to_frontend' => $this->boolean('language_add_to_frontend'),
            'language_add_to_backend' => $this->boolean('language_add_to_backend'),
        ]);
    }

    private function languageSearchRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! app(DomainLanguageActivator::class)->canResolve((string) $value)) {
                $fail(__('No language could be found for this search.'));
            }
        };
    }

    private function googleFontUrlRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! app(GoogleFontUrl::class)->isValid($value)) {
                $fail(__('Use a Google Fonts stylesheet URL from fonts.googleapis.com/css or fonts.googleapis.com/css2.'));
            }
        };
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function onlyRulesForStep(array $rules, string $step): array
    {
        $fields = match ($step) {
            'identity' => [
                'host',
                'aliases_text',
                'aliases',
                'aliases.*',
                'name',
                'company_name',
                'default_locale',
                'is_active',
                'is_development',
                'sort_order',
            ],
            'languages' => [
                'active_frontend_locales',
                'active_frontend_locales.*',
                'active_backend_locales',
                'active_backend_locales.*',
                'language_search',
                'language_add_to_frontend',
                'language_add_to_backend',
            ],
            'template' => [
                'website_template_id',
                'template_settings',
                ...array_values(array_filter(
                    array_keys($rules),
                    fn (string $key): bool => str_starts_with($key, 'template_settings.'),
                )),
            ],
            'seo' => [
                'title_separator',
                'canonical_base_url',
                'default_meta_title',
                'default_meta_description',
                'default_og_title',
                'default_og_description',
                'default_og_image',
                'robots',
            ],
            'integrations' => [
                'public_integrations',
                ...array_values(array_filter(
                    array_keys($rules),
                    fn (string $key): bool => str_starts_with($key, 'public_integrations.'),
                )),
            ],
            'security' => [
                'settings',
                'settings.security',
                'settings.security.backend_two_factor_required',
            ],
            'social-contact' => [
                'social_links',
                'social_links.*.platform',
                'social_links.*.label',
                'social_links.*.icon',
                'social_links.*.url',
                'contact_form_id',
            ],
            'favicon' => [
                'favicon_logo',
                'favicon_svg',
            ],
            default => [],
        };

        return array_intersect_key($rules, array_flip($fields));
    }
}
