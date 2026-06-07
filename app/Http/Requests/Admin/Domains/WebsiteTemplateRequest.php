<?php

namespace App\Http\Requests\Admin\Domains;

use App\Models\Cms\WebsiteTemplate;
use App\Support\Domains\GoogleFontUrl;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebsiteTemplateRequest extends FormRequest
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
        $template = $this->route('websiteTemplate');

        return [
            'name' => ['required', 'string', 'max:255'],
            'handle' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/',
                Rule::unique('website_templates', 'handle')->ignore($template instanceof WebsiteTemplate ? $template->id : null),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'stylesheet_path' => ['nullable', 'string', 'max:255'],
            'asset_path' => ['nullable', 'string', 'max:255'],
            'view_path' => ['nullable', 'string', 'max:255'],
            'default_settings' => ['array'],
            'defined_sections' => ['array'],
            'defined_sections.*.handle' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:[_-][a-z0-9]+)*$/'],
            'defined_sections.*.label' => ['nullable', 'string', 'max:255'],
            'defined_sections.*.type' => ['nullable', Rule::in(['banner', 'mixed'])],
            'default_settings.primary_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'default_settings.secondary_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'default_settings.tertiary_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'default_settings.accent_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'default_settings.surface_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'default_settings.canvas_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'default_settings.light_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'default_settings.grey_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'default_settings.dark_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'default_settings.ink_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'default_settings.muted_ink_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'default_settings.base_font_family' => ['nullable', 'string', 'max:255'],
            'default_settings.heading_font_family' => ['nullable', 'string', 'max:255'],
            'default_settings.base_font_google_url' => ['nullable', 'url', 'max:2048', $this->googleFontUrlRule()],
            'default_settings.heading_font_google_url' => ['nullable', 'url', 'max:2048', $this->googleFontUrlRule()],
            'default_settings.title_style' => ['nullable', Rule::in(['strong', 'quiet', 'editorial'])],
            'default_settings.button_style' => ['nullable', Rule::in(array_keys(config('cms_domains.button_styles')))],
            'default_settings.button_radius' => ['nullable', 'regex:/^\d+(?:\.\d+)?(?:px|rem|em|%)$/'],
            'default_settings.content_width' => ['nullable', 'regex:/^\d+(?:\.\d+)?(?:px|rem|em|%)$/'],
            'default_settings.wrapper_width' => ['nullable', 'regex:/^\d+(?:\.\d+)?(?:px|rem|em|%)$/'],
            'default_settings.logo_width' => ['nullable', 'regex:/^\d+(?:\.\d+)?(?:px|rem|em|%)$/'],
            'default_settings.logo_height' => ['nullable', 'regex:/^\d+(?:\.\d+)?(?:px|rem|em|%)$/'],
            'default_settings.hero_height' => ['nullable', 'regex:/^\d+(?:\.\d+)?(?:px|rem|em|%)$/'],
            'default_settings.logo_path' => ['nullable', 'string', 'max:255'],
            'default_settings.hero_image_path' => ['nullable', 'string', 'max:255'],
            'default_settings.show_usp_bar' => ['nullable', 'boolean'],
            'default_settings.sticky_header' => ['nullable', 'boolean'],
            'default_settings.show_hero' => ['nullable', 'boolean'],
            'default_settings.show_footer_credit' => ['nullable', 'boolean'],
            'default_settings.search_enabled' => ['nullable', 'boolean'],
            'default_settings.usp_items' => ['nullable', 'string', 'max:2000'],
            'default_settings.footer_contact_title' => ['nullable', 'string', 'max:120'],
            'default_settings.footer_contact_text' => ['nullable', 'string', 'max:255'],
            'default_settings.footer_social_title' => ['nullable', 'string', 'max:120'],
            'default_settings.footer_social_text' => ['nullable', 'string', 'max:500'],
            'default_settings.footer_content_title' => ['nullable', 'string', 'max:120'],
            'default_settings.footer_content_text' => ['nullable', 'string', 'max:1000'],
            'default_settings.footer_credit_label' => ['nullable', 'string', 'max:120'],
            'default_settings.footer_credit_url' => ['nullable', 'url', 'max:255'],
            'default_settings.social_placement' => ['nullable', Rule::in(array_keys(config('cms_domains.placement_options')))],
            'default_settings.contact_form_placement' => ['nullable', Rule::in(array_keys(config('cms_domains.placement_options')))],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
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
}
