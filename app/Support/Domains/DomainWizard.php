<?php

namespace App\Support\Domains;

use App\Models\Cms\Domain;

class DomainWizard
{
    /**
     * @return array<string, array{label: string}>
     */
    public static function steps(): array
    {
        return [
            'identity' => ['label' => 'Domain'],
            'languages' => ['label' => 'Languages'],
            'template' => ['label' => 'Template'],
            'seo' => ['label' => 'SEO'],
            'integrations' => ['label' => 'Integrations'],
            'security' => ['label' => 'Security'],
            'social-contact' => ['label' => 'Social & contact'],
            'favicon' => ['label' => 'Favicon'],
            'review' => ['label' => 'Review'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::steps());
    }

    public static function normalize(?string $step, string $fallback = 'identity'): string
    {
        if (is_string($step) && in_array($step, self::keys(), true)) {
            return $step;
        }

        return in_array($fallback, self::keys(), true) ? $fallback : 'identity';
    }

    public static function next(string $step): string
    {
        $keys = self::keys();
        $index = array_search(self::normalize($step), $keys, true);

        if ($index === false || ! isset($keys[$index + 1])) {
            return self::normalize($step);
        }

        return $keys[$index + 1];
    }

    public static function previous(string $step): string
    {
        $keys = self::keys();
        $index = array_search(self::normalize($step), $keys, true);

        if ($index === false || ! isset($keys[$index - 1])) {
            return self::normalize($step);
        }

        return $keys[$index - 1];
    }

    public static function defaultStepFor(Domain $domain): string
    {
        if (! $domain->exists) {
            return 'identity';
        }

        foreach (self::completion($domain) as $step => $complete) {
            if (! $complete) {
                return $step;
            }
        }

        return 'review';
    }

    /**
     * @return array<string, bool>
     */
    public static function completion(Domain $domain): array
    {
        return [
            'identity' => filled($domain->host) && filled($domain->name),
            'languages' => count($domain->activeFrontendLocales()) > 0 || count($domain->activeBackendLocales()) > 0,
            'template' => filled($domain->website_template_id),
            'seo' => filled($domain->default_meta_description) || filled($domain->default_meta_title),
            'integrations' => count($domain->public_integrations ?? []) > 0 || filled($domain->integration_credentials),
            'security' => true,
            'social-contact' => count($domain->social_links ?? []) > 0 || filled($domain->contact_form_id),
            'favicon' => filled($domain->favicon_svg_path),
            'review' => $domain->exists && $domain->is_active,
        ];
    }
}
