<?php

namespace App\Support\Domains;

use App\Models\Cms\Domain;

class DomainThemeCss
{
    public function __construct(private readonly GoogleFontUrl $googleFonts) {}

    public function render(?Domain $domain): string
    {
        $settings = [
            ...config('cms_domains.default_template_settings', []),
            ...($domain?->effectiveTemplateSettings() ?? []),
        ];
        $baseFont = $this->googleFonts->familyStack(
            $settings['base_font_google_url'] ?? null,
            $this->genericFontFallback($settings['base_font_family'] ?? null),
        ) ?? $this->font($settings['base_font_family'] ?? null, '"Open Sans", Arial, sans-serif');
        $headingFont = $this->googleFonts->familyStack(
            $settings['heading_font_google_url'] ?? null,
            $this->genericFontFallback($settings['heading_font_family'] ?? null),
        ) ?? $this->font($settings['heading_font_family'] ?? null, $baseFont);

        $variables = [
            '--color-brand' => $this->color($settings['primary_color'] ?? null, '#0f6f7a'),
            '--color-brand-strong' => $this->color($settings['secondary_color'] ?? null, '#1b1b1b'),
            '--color-tertiary' => $this->color($settings['tertiary_color'] ?? null, '#2d7fc5'),
            '--color-accent' => $this->color($settings['accent_color'] ?? $settings['tertiary_color'] ?? null, '#d86445'),
            '--color-surface' => $this->color($settings['surface_color'] ?? null, '#ffffff'),
            '--color-canvas' => $this->color($settings['canvas_color'] ?? null, '#f5f7fa'),
            '--color-light' => $this->color($settings['light_color'] ?? null, '#eef3f7'),
            '--color-grey' => $this->color($settings['grey_color'] ?? null, '#d8dee8'),
            '--color-dark' => $this->color($settings['dark_color'] ?? null, '#1b1b1b'),
            '--color-ink' => $this->color($settings['ink_color'] ?? null, '#1f242b'),
            '--color-ink-muted' => $this->color($settings['muted_ink_color'] ?? null, '#667085'),
            '--font-body' => $baseFont,
            '--font-heading' => $headingFont,
            '--radius-small' => $this->length($settings['button_radius'] ?? null, '3px'),
            '--content-width' => $this->length($settings['content_width'] ?? null, '1400px'),
            '--template-wrapper-width' => $this->length($settings['wrapper_width'] ?? null, '1400px'),
            '--template-logo-width' => $this->length($settings['logo_width'] ?? null, '150px'),
            '--template-logo-height' => $this->length($settings['logo_height'] ?? null, '75px'),
            '--template-hero-height' => $this->length($settings['hero_height'] ?? null, '448px'),
            '--template-hero-image' => $this->imageUrl($settings['hero_image_path'] ?? null, 'site/templates/default/assets/default-eyecatcher-image.jpg'),
        ];

        $buttonStyle = $this->keyword($settings['button_style'] ?? null, ['solid', 'outline', 'soft'], 'solid');
        $titleStyle = $this->keyword($settings['title_style'] ?? null, ['strong', 'quiet', 'editorial'], 'strong');

        $css = ":root {\n";

        foreach ($variables as $name => $value) {
            $css .= "  {$name}: {$value};\n";
        }

        $css .= "}\n";
        $css .= ".site-brand { color: var(--color-brand); }\n";
        $css .= ".page-block-button, .form-builder-form button { border-radius: var(--radius-small); }\n";

        if ($buttonStyle === 'outline') {
            $css .= ".page-block-button--primary, .form-builder-form button { background: var(--color-surface); color: var(--color-brand); }\n";
        } elseif ($buttonStyle === 'soft') {
            $css .= ".page-block-button--primary, .form-builder-form button { background: color-mix(in srgb, var(--color-brand) 12%, var(--color-surface)); color: var(--color-brand); }\n";
        }

        if ($titleStyle === 'quiet') {
            $css .= ".page-hero-title { font-weight: 600; }\n";
        } elseif ($titleStyle === 'editorial') {
            $css .= ".page-hero-title { font-style: italic; }\n";
        }

        return $css;
    }

    private function color(mixed $value, string $fallback): string
    {
        if (! is_string($value)) {
            return $fallback;
        }

        $value = trim($value);

        return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $value) === 1
            ? $value
            : $fallback;
    }

    private function font(mixed $value, string $fallback): string
    {
        if (! is_string($value) || trim($value) === '') {
            return $fallback;
        }

        $value = trim($value);

        return preg_match('/^[A-Za-z0-9\s",._-]+$/', $value) === 1
            ? $value
            : $fallback;
    }

    private function genericFontFallback(mixed $value): string
    {
        if (! is_string($value)) {
            return 'sans-serif';
        }

        if (str_contains(strtolower($value), 'monospace')) {
            return 'monospace';
        }

        if (str_contains(strtolower($value), 'serif') && ! str_contains(strtolower($value), 'sans-serif')) {
            return 'serif';
        }

        return 'sans-serif';
    }

    private function length(mixed $value, string $fallback): string
    {
        if (! is_string($value) || trim($value) === '') {
            return $fallback;
        }

        $value = trim($value);

        return preg_match('/^\d+(?:\.\d+)?(?:px|rem|em|%)$/', $value) === 1
            ? $value
            : $fallback;
    }

    private function imageUrl(mixed $value, string $fallback): string
    {
        $path = is_string($value) && trim($value) !== ''
            ? trim($value)
            : $fallback;

        if (
            preg_match('/^https?:\/\/[A-Za-z0-9._~:\/?#\[\]@!$&\'()*+,;=%-]+$/', $path) !== 1
            && preg_match('/^[A-Za-z0-9._~\/%-]+$/', $path) !== 1
        ) {
            $path = $fallback;
        }

        $url = preg_match('/^https?:\/\//', $path) === 1
            ? $path
            : asset(ltrim($path, '/'));

        return 'url("'.addcslashes($url, '"\\').'")';
    }

    /**
     * @param  list<string>  $allowed
     */
    private function keyword(mixed $value, array $allowed, string $fallback): string
    {
        return is_string($value) && in_array($value, $allowed, true)
            ? $value
            : $fallback;
    }
}
