<?php

namespace Database\Seeders;

use App\Models\Cms\CmsLanguage;
use App\Models\Cms\Domain;
use App\Models\Cms\DomainAlias;
use App\Models\Cms\Page;
use App\Models\Cms\WebsiteTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DomainTemplateSeeder extends Seeder
{
    public function run(): void
    {
        CmsLanguage::query()
            ->whereIn('code', ['nl', 'en', 'fr'])
            ->update(['is_enabled' => true, 'status' => 'active']);

        $defaultTemplate = WebsiteTemplate::query()->updateOrCreate(
            ['handle' => 'default'],
            [
                'name' => 'Default',
                'description' => 'Default front-end template for custom websites with shared assets and domain overrides.',
                'stylesheet_path' => 'resources/scss/site/templates/default/_index.scss',
                'asset_path' => 'public/site/templates/default',
                'view_path' => 'resources/views/site/templates/default',
                'default_settings' => [
                    'primary_color' => '#ffa300',
                    'secondary_color' => '#272720',
                    'tertiary_color' => '#00a287',
                    'accent_color' => '#ffa300',
                    'surface_color' => '#ffffff',
                    'canvas_color' => '#ffffff',
                    'light_color' => '#f0f0f0',
                    'grey_color' => '#e0e0e0',
                    'dark_color' => '#2d2d29',
                    'ink_color' => '#2d2d29',
                    'muted_ink_color' => '#6f6f68',
                    'base_font_family' => '"Open Sans", Arial, sans-serif',
                    'heading_font_family' => '"Open Sans", Arial, sans-serif',
                    'button_style' => 'solid',
                    'button_radius' => '3px',
                    'content_width' => '1400px',
                    'wrapper_width' => '1400px',
                    'logo_width' => '150px',
                    'logo_height' => '75px',
                    'hero_height' => '448px',
                    'logo_path' => 'site/templates/default/assets/logo.svg',
                    'hero_image_path' => 'site/templates/default/assets/default-eyecatcher-image.jpg',
                    'show_usp_bar' => true,
                    'sticky_header' => true,
                    'show_hero' => true,
                    'show_footer_credit' => true,
                    'usp_items' => [
                        'Responsive maatwerk',
                        'SEO vriendelijke basis',
                        'Veilig en snel beheer',
                    ],
                    'footer_contact_title' => 'Contactgegevens',
                    'footer_social_title' => 'Social media',
                    'footer_social_text' => 'Volg ons op social media en blijf op de hoogte.',
                    'footer_content_title' => 'Over ons',
                    'footer_content_text' => 'Een modern Laravel basisplatform voor maatwerk websites.',
                    'footer_credit_label' => 'HPU internet services',
                    'footer_credit_url' => 'https://hpu.nl/',
                    'social_placement' => 'footer',
                    'contact_form_placement' => 'none',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        $localDomain = $this->domain([
            'host' => Domain::normalizeHost(config('cms_domains.local_domain')),
            'name' => 'Base CMS Local',
            'company_name' => 'Base CMS',
            'website_template_id' => $defaultTemplate->id,
            'default_locale' => 'nl',
            'default_meta_description' => 'Local Herd domain for testing the Base CMS frontend.',
            'template_settings' => [
                'primary_color' => '#ffa300',
                'button_style' => 'solid',
            ],
            'is_development' => true,
            'sort_order' => 0,
        ]);

        $dutchDomain = $this->domain([
            'host' => 'www.example.nl',
            'name' => 'Example Nederland',
            'company_name' => 'Example BV',
            'website_template_id' => $defaultTemplate->id,
            'default_locale' => 'nl',
            'default_meta_description' => 'Seeded Dutch domain for frontend domain switching.',
            'template_settings' => [
                'primary_color' => '#1d4ed8',
                'secondary_color' => '#172554',
                'accent_color' => '#be123c',
                'button_style' => 'soft',
            ],
            'sort_order' => 1,
        ], ['example.nl']);

        $frenchDomain = $this->domain([
            'host' => 'www.example.fr',
            'name' => 'Example France',
            'company_name' => 'Example SARL',
            'website_template_id' => $defaultTemplate->id,
            'default_locale' => 'fr',
            'default_meta_description' => 'Seeded French domain for frontend domain switching.',
            'template_settings' => [
                'primary_color' => '#713f12',
                'secondary_color' => '#3f2a13',
                'accent_color' => '#0f766e',
                'button_style' => 'outline',
                'title_style' => 'editorial',
            ],
            'sort_order' => 2,
        ], ['example.fr']);

        $this->page($localDomain, 'Base CMS Local', 'The Herd local domain is ready for frontend template testing.');
        $this->page($dutchDomain, 'Nederlandse website', 'Deze seeded domeinvariant gebruikt eigen content en template-instellingen.');
        $this->page($frenchDomain, 'Site francais', 'Cette variante de domaine utilise son propre contenu et ses propres reglages.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $aliases
     */
    private function domain(array $attributes, array $aliases = []): Domain
    {
        $domain = Domain::query()->updateOrCreate(
            ['host' => $attributes['host']],
            [
                'uuid' => (string) Str::uuid(),
                'title_separator' => '|',
                'active_frontend_locales' => ['nl', 'en', 'fr'],
                'active_backend_locales' => ['nl', 'en'],
                'is_active' => true,
                'social_links' => [
                    ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://www.linkedin.com'],
                ],
                ...$attributes,
            ],
        );

        foreach ($aliases as $alias) {
            DomainAlias::query()->updateOrCreate(
                ['host' => Domain::normalizeHost($alias)],
                [
                    'uuid' => (string) Str::uuid(),
                    'domain_id' => $domain->id,
                    'is_primary' => false,
                ],
            );
        }

        return $domain;
    }

    private function page(Domain $domain, string $title, string $body): void
    {
        Page::query()->updateOrCreate(
            [
                'domain_id' => $domain->id,
                'slug' => 'home',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'title' => $title,
                'navigation_label' => 'Home',
                'excerpt' => $domain->default_meta_description,
                'body' => $body,
                'meta_title' => $title,
                'meta_description' => $domain->default_meta_description,
                'template' => 'default',
                'status' => 'published',
                'sort_order' => 0,
                'published_at' => now(),
            ],
        );
    }
}
