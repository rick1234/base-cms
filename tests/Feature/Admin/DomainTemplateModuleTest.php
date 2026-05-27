<?php

namespace Tests\Feature\Admin;

use App\Models\Cms\CmsLanguage;
use App\Models\Cms\Domain;
use App\Models\Cms\WebsiteTemplate;
use App\Models\User;
use Database\Seeders\CountryLanguageSeeder;
use Database\Seeders\DomainTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DomainTemplateModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_template_defaults(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/templates', [
                'name' => 'Clean Corporate',
                'handle' => 'clean-corporate',
                'description' => 'Reusable corporate template.',
                'default_settings' => [
                    'primary_color' => '#102030',
                    'secondary_color' => '#203040',
                    'tertiary_color' => '#304050',
                    'accent_color' => '#405060',
                    'base_font_google_url' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap',
                    'heading_font_google_url' => 'https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@600;700&display=swap',
                    'button_style' => 'solid',
                    'button_radius' => '6px',
                    'content_width' => '70rem',
                    'social_placement' => 'footer',
                    'contact_form_placement' => 'footer',
                ],
                'is_active' => '1',
                'sort_order' => '10',
            ])
            ->assertRedirect();

        $template = WebsiteTemplate::query()->firstOrFail();

        $this->assertSame('clean-corporate', $template->handle);
        $this->assertSame('resources/scss/site/templates/clean-corporate/_index.scss', $template->stylesheet_path);
        $this->assertSame('resources/views/site/templates/clean-corporate', $template->view_path);
        $this->assertSame('public/site/templates/clean-corporate', $template->asset_path);
        $this->assertSame('#102030', $template->default_settings['primary_color']);
        $this->assertSame('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap', $template->default_settings['base_font_google_url']);
        $this->assertSame('https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@600;700&display=swap', $template->default_settings['heading_font_google_url']);
    }

    public function test_admin_can_start_domain_setup_from_dashboard_and_domain_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Website setup')
            ->assertSee('Start domain setup');

        $this->actingAs($admin)
            ->get('/admin/domains')
            ->assertOk()
            ->assertSee('Start domain setup');

        $this->actingAs($admin)
            ->get('/admin/templates/create')
            ->assertOk()
            ->assertSee('Technical name')
            ->assertSee('Base Google font link')
            ->assertSee('Heading Google font link')
            ->assertSee('data-google-font-preview-input', false)
            ->assertSee('template-font-preview-sample', false)
            ->assertSee('resources/scss/site/templates/{technical-name}/_index.scss')
            ->assertSee('data-coloris', false)
            ->assertDontSee('Schemas')
            ->assertDontSee('Settings schema')
            ->assertDontSee('Custom settings schema');

        $this->actingAs($admin)
            ->get('/admin/domains/create')
            ->assertOk()
            ->assertSee('Domain setup steps')
            ->assertSee('base-cms.test')
            ->assertSee('Primary host')
            ->assertSee('Domain')
            ->assertSee('Languages')
            ->assertSee('Template')
            ->assertSee('SEO')
            ->assertSee('Integrations')
            ->assertSee('Social &amp; contact', false)
            ->assertSee('Favicon')
            ->assertSee('Review')
            ->assertDontSee('Website template')
            ->assertDontSee('Title separator')
            ->assertDontSee('data-coloris', false)
            ->assertDontSee('Custom template settings')
            ->assertDontSee('Private integration credentials');

        $domain = Domain::query()->create([
            'host' => 'www.example.test',
            'name' => 'Example',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.domains.edit', ['domain' => $domain, 'step' => 'template']))
            ->assertOk()
            ->assertSee('Website template')
            ->assertSee('data-coloris', false)
            ->assertDontSee('Primary host');

        $this->actingAs($admin)
            ->get(route('admin.domains.edit', ['domain' => $domain, 'step' => 'social-contact']))
            ->assertOk()
            ->assertSee('data-domain-social-sortable-list', false)
            ->assertSee('data-domain-social-handle', false)
            ->assertSee('Contact form');

        $this->actingAs($admin)
            ->get(route('admin.domains.edit', ['domain' => $domain, 'step' => 'favicon']))
            ->assertOk()
            ->assertSee('Logo for favicon')
            ->assertSee('image/svg+xml,image/png,image/jpeg,image/webp', false);

        $this->actingAs($admin)
            ->get(route('admin.domains.edit', ['domain' => $domain, 'step' => 'review']))
            ->assertOk()
            ->assertSee('Frontend languages')
            ->assertSee('NL')
            ->assertDontSee('All enabled languages');
    }

    public function test_admin_can_create_domain_with_safe_settings_and_favicon_assets(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $template = WebsiteTemplate::query()->create([
            'handle' => 'base',
            'name' => 'Base Template',
            'is_active' => true,
        ]);
        $favicon = UploadedFile::fake()->image('logo.png', 320, 180);

        $this->actingAs($admin)
            ->post('/admin/domains', [
                '_domain_step' => 'identity',
                '_next_step' => 'languages',
                'host' => 'www.example.test',
                'aliases_text' => "example.test\nexample.nl",
                'name' => 'Example',
                'company_name' => 'Example BV',
                'default_locale' => 'nl',
                'is_active' => '1',
                'is_development' => '1',
                'sort_order' => '0',
            ])
            ->assertRedirect();

        $domain = Domain::query()->with('aliases')->firstOrFail();

        $this->actingAs($admin)
            ->put('/admin/domains/'.$domain->id, [
                '_domain_step' => 'languages',
                '_next_step' => 'template',
                'active_frontend_locales' => ['nl', 'fr'],
                'active_backend_locales' => ['nl', 'en'],
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put('/admin/domains/'.$domain->id, [
                '_domain_step' => 'template',
                '_next_step' => 'seo',
                'website_template_id' => $template->id,
                'template_settings' => [
                    'primary_color' => '#112233',
                    'button_style' => 'outline',
                    'button_radius' => '5px',
                    'content_width' => '68rem',
                    'social_placement' => 'header',
                    'contact_form_placement' => 'footer',
                ],
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put('/admin/domains/'.$domain->id, [
                '_domain_step' => 'seo',
                '_next_step' => 'integrations',
                'title_separator' => '|',
                'default_meta_description' => 'Default description.',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put('/admin/domains/'.$domain->id, [
                '_domain_step' => 'integrations',
                '_next_step' => 'social-contact',
                'public_integrations' => [
                    'google_analytics_measurement_id' => 'G-1234567890',
                ],
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put('/admin/domains/'.$domain->id, [
                '_domain_step' => 'social-contact',
                '_next_step' => 'favicon',
                'social_links' => [
                    ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://linkedin.com/company/example'],
                    ['platform' => 'youtube', 'label' => 'YouTube', 'url' => 'https://youtube.com/@example'],
                ],
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put('/admin/domains/'.$domain->id, [
                '_domain_step' => 'favicon',
                '_next_step' => 'review',
                'favicon_logo' => $favicon,
            ])
            ->assertRedirect();

        $domain = Domain::query()->with('aliases')->firstOrFail();

        $this->assertSame('www.example.test', $domain->host);
        $this->assertSame(['nl', 'fr'], $domain->active_frontend_locales);
        $this->assertSame($template->id, $domain->website_template_id);
        $this->assertSame('#112233', $domain->template_settings['primary_color']);
        $this->assertSame('Default description.', $domain->default_meta_description);
        $this->assertSame('G-1234567890', $domain->public_integrations['google_analytics_measurement_id']);
        $this->assertSame('https://linkedin.com/company/example', $domain->social_links[0]['url']);
        $this->assertSame('https://youtube.com/@example', $domain->social_links[1]['url']);
        $this->assertSame(['base-cms.test', 'example.nl', 'example.test'], $domain->aliases->pluck('host')->sort()->values()->all());

        Storage::disk('public')->assertExists($domain->favicon_assets['source']);
        Storage::disk('public')->assertExists($domain->favicon_assets['icon']);
        Storage::disk('public')->assertExists($domain->favicon_assets['icon_32']);
        Storage::disk('public')->assertExists($domain->favicon_assets['apple_touch_icon']);
        Storage::disk('public')->assertExists($domain->favicon_assets['manifest']);
    }

    public function test_domain_language_step_can_enable_language_from_search(): void
    {
        $this->seed(CountryLanguageSeeder::class);

        $admin = User::factory()->admin()->create();
        $domain = Domain::query()->create([
            'host' => 'www.example.test',
            'name' => 'Example',
            'active_frontend_locales' => ['nl'],
            'active_backend_locales' => ['nl'],
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('languages', [
            'code' => 'de',
            'is_enabled' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.domains.edit', ['domain' => $domain, 'step' => 'languages']))
            ->assertOk()
            ->assertSee('Add website language')
            ->assertSee('German');

        $this->actingAs($admin)
            ->put(route('admin.domains.update', $domain), [
                '_domain_step' => 'languages',
                '_next_step' => 'template',
                'active_frontend_locales' => ['nl'],
                'active_backend_locales' => ['nl'],
                'language_search' => 'German',
                'language_add_to_frontend' => '1',
                'language_add_to_backend' => '1',
            ])
            ->assertRedirect(route('admin.domains.edit', ['domain' => $domain, 'step' => 'template']));

        $german = CmsLanguage::query()->where('code', 'de')->firstOrFail();
        $domain->refresh();

        $this->assertTrue($german->is_enabled);
        $this->assertSame('active', $german->status);
        $this->assertSame(['nl', 'de'], $domain->active_frontend_locales);
        $this->assertSame(['nl', 'de'], $domain->active_backend_locales);
    }

    public function test_domain_template_seeder_creates_local_and_sample_domains(): void
    {
        $this->seed(DomainTemplateSeeder::class);

        $this->assertDatabaseHas('domains', [
            'host' => 'base-cms.test',
            'is_development' => true,
        ]);
        $this->assertDatabaseHas('domains', ['host' => 'www.example.nl']);
        $this->assertDatabaseHas('domains', ['host' => 'www.example.fr']);
        $this->assertDatabaseHas('website_templates', [
            'handle' => 'default',
            'stylesheet_path' => 'resources/scss/site/templates/default/_index.scss',
            'asset_path' => 'public/site/templates/default',
        ]);
    }
}
