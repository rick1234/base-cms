<?php

namespace Tests\Feature\Frontend;

use App\Models\Cms\Domain;
use App\Models\Cms\Page;
use App\Models\Cms\WebsiteTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_uses_domain_specific_page_and_metadata(): void
    {
        $template = WebsiteTemplate::query()->create([
            'handle' => 'brand',
            'name' => 'Brand Template',
            'default_settings' => [
                'primary_color' => '#123456',
            ],
            'is_active' => true,
        ]);

        $domain = Domain::query()->create([
            'host' => 'example.test',
            'name' => 'Example Site',
            'website_template_id' => $template->id,
            'default_locale' => 'nl',
            'title_separator' => '~',
            'default_meta_description' => 'Domain fallback description.',
            'template_settings' => [
                'primary_color' => '#abcdef',
                'button_style' => 'outline',
                'base_font_google_url' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap',
                'heading_font_google_url' => 'https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@600;700&display=swap',
            ],
            'is_active' => true,
        ]);

        Page::factory()->create([
            'slug' => 'about',
            'title' => 'Global About',
            'body' => 'Global page body.',
        ]);

        Page::factory()->create([
            'domain_id' => $domain->id,
            'slug' => 'about',
            'title' => 'Domain About',
            'body' => 'Domain page body.',
            'meta_title' => 'Domain SEO title',
            'meta_description' => null,
        ]);

        $this->withHeader('Host', 'example.test')
            ->get('/about')
            ->assertOk()
            ->assertSee('Domain page body.')
            ->assertDontSee('Global page body.')
            ->assertSee('Domain SEO title ~ Example Site')
            ->assertSee('Domain fallback description.')
            ->assertSee('site/templates/default/assets/logo.svg')
            ->assertSee('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&amp;display=swap', false)
            ->assertSee('https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@600;700&amp;display=swap', false)
            ->assertSee('Responsive maatwerk');

        $this->withHeader('Host', 'example.test')
            ->get('/__domain/theme.css')
            ->assertOk()
            ->assertSee('--color-brand: #abcdef', false)
            ->assertSee('--font-body: "Roboto", sans-serif', false)
            ->assertSee('--font-heading: "Roboto Slab", sans-serif', false)
            ->assertSee('--template-hero-image: url(', false)
            ->assertSee('background: var(--color-surface)', false);
    }

    public function test_development_preview_session_can_switch_domains_on_test_hosts(): void
    {
        $firstDomain = Domain::query()->create([
            'host' => 'first.test',
            'name' => 'First Site',
            'is_active' => true,
            'is_development' => true,
        ]);

        $secondDomain = Domain::query()->create([
            'host' => 'second.test',
            'name' => 'Second Site',
            'is_active' => true,
        ]);

        Page::factory()->create([
            'domain_id' => $firstDomain->id,
            'slug' => 'home',
            'title' => 'First Home',
        ]);

        Page::factory()->create([
            'domain_id' => $secondDomain->id,
            'slug' => 'home',
            'title' => 'Second Home',
        ]);

        $this->withHeader('Host', 'local.test')
            ->withSession(['cms.preview_domain_id' => $secondDomain->id])
            ->get('/')
            ->assertOk()
            ->assertSee('Second Home')
            ->assertSee('Domain toolbar')
            ->assertSee('base-cms.test')
            ->assertSee('second.test')
            ->assertSee('first.test');
    }

    public function test_frontend_search_uses_domain_scoped_public_content(): void
    {
        $domain = Domain::query()->create([
            'host' => 'search.test',
            'name' => 'Search Site',
            'template_settings' => [
                'search_enabled' => '1',
            ],
            'is_active' => true,
        ]);

        Page::factory()->create([
            'slug' => 'shared',
            'title' => 'Shared Result',
            'body' => 'Needle content.',
        ]);

        Page::factory()->create([
            'domain_id' => $domain->id,
            'slug' => 'domain-result',
            'title' => 'Domain Result',
            'body' => 'Needle content.',
        ]);

        $this->withHeader('Host', 'search.test')
            ->get('/search?q=Needle')
            ->assertOk()
            ->assertSee('Domain Result')
            ->assertSee('Shared Result')
            ->assertSee('site-search-query');
    }

    public function test_empty_domain_locale_settings_fall_back_to_domain_default_only(): void
    {
        $domain = Domain::query()->create([
            'host' => 'locale.test',
            'name' => 'Locale Site',
            'default_locale' => 'nl',
            'active_frontend_locales' => [],
            'active_backend_locales' => [],
            'is_active' => true,
        ]);

        $this->assertSame(['nl'], $domain->activeFrontendLocales());
        $this->assertSame(['nl'], $domain->activeBackendLocales());

        Page::factory()->create([
            'domain_id' => $domain->id,
            'slug' => 'home',
            'title' => 'Locale Home',
        ]);

        $this->withHeader('Host', 'locale.test')
            ->from('/')
            ->post('/locale/en')
            ->assertSessionHasErrors('locale');
    }
}
