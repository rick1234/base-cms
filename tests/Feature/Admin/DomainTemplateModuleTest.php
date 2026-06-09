<?php

namespace Tests\Feature\Admin;

use App\Models\Cms\CmsLanguage;
use App\Models\Cms\Domain;
use App\Models\Cms\WebsiteTemplate;
use App\Livewire\Admin\Domains\DomainOverview;
use App\Livewire\Admin\Templates\TemplateOverview;
use App\Livewire\Admin\Templates\TemplateSectionEditor;
use App\Livewire\Admin\Templates\TemplateUspSetEditor;
use App\Models\User;
use Database\Seeders\CountryLanguageSeeder;
use Database\Seeders\DomainTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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
                'defined_sections' => [
                    [
                        'label' => 'Homepage Right Block',
                        'handle' => 'homepage_right_block',
                        'type' => 'banner',
                    ],
                    [
                        'label' => 'Catalogus upsale banner',
                        'handle' => 'catalogus_upsale_banner',
                        'type' => 'mixed',
                    ],
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
        $this->assertSame('homepage_right_block', $template->defined_sections[0]['handle']);
        $this->assertSame('Catalogus upsale banner', $template->defined_sections[1]['label']);
        $this->assertSame('mixed', $template->defined_sections[1]['type']);
    }

    public function test_admin_can_filter_template_overview_and_update_status(): void
    {
        $admin = User::factory()->admin()->create();

        $corporate = WebsiteTemplate::query()->create([
            'handle' => 'corporate',
            'name' => 'Corporate Template',
            'is_active' => true,
            'sort_order' => 20,
            'defined_sections' => [
                ['label' => 'Homepage hero', 'handle' => 'homepage_hero', 'type' => 'banner'],
                ['label' => 'Footer banner', 'handle' => 'footer_banner', 'type' => 'banner'],
            ],
        ]);
        $campaign = WebsiteTemplate::query()->create([
            'handle' => 'campaign',
            'name' => 'Campaign Template',
            'is_active' => false,
            'sort_order' => 10,
        ]);

        Domain::query()->create([
            'host' => 'www.example.test',
            'name' => 'Example',
            'website_template_id' => $corporate->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/templates')
            ->assertOk()
            ->assertSee('Template overzicht')
            ->assertSeeLivewire(TemplateOverview::class)
            ->assertSee('template-overview-container', false)
            ->assertSee('Corporate Template')
            ->assertSee('Campaign Template')
            ->assertSee('quick-status', false);

        Livewire::actingAs($admin)
            ->test(TemplateOverview::class)
            ->assertSee('Corporate Template')
            ->assertSee('Campaign Template')
            ->set('draftName', 'Corporate')
            ->call('applyFilters')
            ->assertSee('Corporate Template')
            ->assertDontSee('Campaign Template')
            ->set('draftName', '')
            ->set('draftStatus', 'inactive')
            ->call('applyFilters')
            ->assertSee('Campaign Template')
            ->assertDontSee('Corporate Template')
            ->call('sortBy', 'name', 'desc')
            ->assertSet('sort', 'name')
            ->assertSet('direction', 'desc');

        $this->actingAs($admin)
            ->patch(route('admin.quick-status.update'), [
                'model' => 'website-template',
                'id' => $campaign->id,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertTrue($campaign->refresh()->is_active);
    }

    public function test_admin_can_start_domain_setup_from_domain_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('dashboard-module-index', false)
            ->assertSee('dashboard-widget-column', false)
            ->assertDontSee('Website setup')
            ->assertDontSee('Start domain setup')
            ->assertDontSee('Create template');

        $this->actingAs($admin)
            ->get('/admin/domains')
            ->assertOk()
            ->assertSee('Start domain setup');

        $this->actingAs($admin)
            ->get('/admin/templates/create')
            ->assertOk()
            ->assertSee('Technical name')
            ->assertSee('tabmenu', false)
            ->assertSee('Template')
            ->assertSee('Instellingen')
            ->assertSee('Gedefinieerde secties')
            ->assertSee('USP-sets')
            ->assertSee('Paden')
            ->assertSee('Voorbeeld')
            ->assertDontSee('Base Google font link')
            ->assertDontSee('Heading Google font link')
            ->assertDontSee('template-wireframe', false)
            ->assertDontSee('data-google-font-preview-input', false)
            ->assertDontSee('template-font-preview-sample', false)
            ->assertSee('resources/scss/site/templates/{technical-name}/_index.scss')
            ->assertDontSee('default_settings[primary_color]', false)
            ->assertDontSee('Schemas')
            ->assertDontSee('Settings schema')
            ->assertDontSee('Custom settings schema');

        $template = WebsiteTemplate::query()->create([
            'handle' => 'wireframe',
            'name' => 'Wireframe Template',
            'is_active' => true,
            'defined_sections' => [
                ['label' => 'Homepage hero', 'handle' => 'homepage_hero', 'type' => 'banner'],
                ['label' => 'Footer banner', 'handle' => 'footer_banner', 'type' => 'banner'],
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.templates.edit', $template))
            ->assertOk()
            ->assertSee('tabmenu', false)
            ->assertSee('Template')
            ->assertSee('Instellingen')
            ->assertSee('Gedefinieerde secties')
            ->assertSee('USP-sets')
            ->assertDontSee('template-wireframe', false);

        $this->actingAs($admin)
            ->get(route('admin.templates.edit.tab', ['websiteTemplate' => $template, 'tab' => 'settings']))
            ->assertOk()
            ->assertSee('Base Google font link')
            ->assertSee('Heading Google font link')
            ->assertSee('data-google-font-preview-input', false)
            ->assertSee('template-font-preview-sample', false);

        $this->actingAs($admin)
            ->get(route('admin.templates.edit.tab', ['websiteTemplate' => $template, 'tab' => 'sections']))
            ->assertOk()
            ->assertSee('template-section-editor', false)
            ->assertSee('template-wireframe', false)
            ->assertSee('Homepage hero')
            ->assertSee('Footer banner');

        $this->actingAs($admin)
            ->get(route('admin.templates.edit.tab', ['websiteTemplate' => $template, 'tab' => 'usp-sets']))
            ->assertOk()
            ->assertSee('template-usp-set-editor', false)
            ->assertSee('USP-sets')
            ->assertSee('Template locatie');

        $this->actingAs($admin)
            ->get(route('admin.templates.edit.tab', ['websiteTemplate' => $template, 'tab' => 'preview']))
            ->assertOk()
            ->assertSee('template-wireframe', false)
            ->assertSee('template-generate-form', false)
            ->assertSee('Frontendbestanden opnieuw genereren');

        $this->actingAs($admin)
            ->get('/admin/domains/create')
            ->assertOk()
            ->assertSee('tabmenu', false)
            ->assertDontSee('domain-wizard', false)
            ->assertSee('base-cms.test')
            ->assertSee('Primary host')
            ->assertSee('Domein')
            ->assertSee('Languages')
            ->assertSee('Template')
            ->assertSee('SEO')
            ->assertSee('Integrations')
            ->assertSee('Social &amp; contact', false)
            ->assertSee('Favicon')
            ->assertSee('Review')
            ->assertDontSee('Website template')
            ->assertDontSee('Title separator')
            ->assertDontSee('template_settings[primary_color]', false)
            ->assertDontSee('Custom template settings')
            ->assertDontSee('Private integration credentials');

        $domain = Domain::query()->create([
            'host' => 'www.example.test',
            'name' => 'Example',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.domains.edit.step', ['domain' => $domain, 'step' => 'template']))
            ->assertOk()
            ->assertSee('tabmenu', false)
            ->assertDontSee('domain-wizard', false)
            ->assertSee('Website template')
            ->assertSee('template_settings[primary_color]', false)
            ->assertDontSee('Primary host');

        $this->actingAs($admin)
            ->get(route('admin.domains.edit.step', ['domain' => $domain, 'step' => 'social-contact']))
            ->assertOk()
            ->assertSee('data-domain-social-sortable-list', false)
            ->assertSee('data-domain-social-handle', false)
            ->assertSee('Contact form');

        $this->actingAs($admin)
            ->get(route('admin.domains.edit.step', ['domain' => $domain, 'step' => 'favicon']))
            ->assertOk()
            ->assertSee('Logo for favicon')
            ->assertSee('image/svg+xml,image/png,image/jpeg,image/webp', false);

        $this->actingAs($admin)
            ->get(route('admin.domains.edit.step', ['domain' => $domain, 'step' => 'review']))
            ->assertOk()
            ->assertSee('Frontend languages')
            ->assertSee('NL')
            ->assertDontSee('All enabled languages');
    }

    public function test_admin_can_filter_domain_overview_and_update_status(): void
    {
        $admin = User::factory()->admin()->create();

        $corporate = WebsiteTemplate::query()->create([
            'handle' => 'corporate',
            'name' => 'Corporate Template',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $campaign = WebsiteTemplate::query()->create([
            'handle' => 'campaign',
            'name' => 'Campaign Template',
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $primary = Domain::query()->create([
            'host' => 'www.example.test',
            'name' => 'Example',
            'website_template_id' => $corporate->id,
            'default_locale' => 'nl',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $inactive = Domain::query()->create([
            'host' => 'campaign.example.test',
            'name' => 'Campaign',
            'website_template_id' => $campaign->id,
            'default_locale' => 'en',
            'is_active' => false,
            'sort_order' => 20,
        ]);

        $this->actingAs($admin)
            ->get('/admin/domains')
            ->assertOk()
            ->assertSee('Domein overzicht')
            ->assertSeeLivewire(DomainOverview::class)
            ->assertSee('domains-overview-container', false)
            ->assertSee('www.example.test')
            ->assertSee('campaign.example.test')
            ->assertSee('quick-status', false);

        Livewire::actingAs($admin)
            ->test(DomainOverview::class)
            ->assertSee('www.example.test')
            ->assertSee('campaign.example.test')
            ->set('draftHost', 'campaign')
            ->call('applyFilters')
            ->assertSee('campaign.example.test')
            ->assertDontSee('www.example.test')
            ->set('draftHost', '')
            ->set('draftTemplateId', (string) $corporate->id)
            ->call('applyFilters')
            ->assertSee('www.example.test')
            ->assertDontSee('campaign.example.test')
            ->set('draftTemplateId', '')
            ->set('draftStatus', 'inactive')
            ->call('applyFilters')
            ->assertSee('campaign.example.test')
            ->assertDontSee('www.example.test')
            ->call('sortBy', 'host', 'desc')
            ->assertSet('sort', 'host')
            ->assertSet('direction', 'desc');

        $this->actingAs($admin)
            ->patch(route('admin.quick-status.update'), [
                'model' => 'domain',
                'id' => $inactive->id,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertTrue($inactive->refresh()->is_active);
        $this->assertTrue($primary->refresh()->is_active);
    }

    public function test_dashboard_uses_module_index_without_setup_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('dashboard-module-index', false)
            ->assertSee('dashboard-widget-column', false)
            ->assertDontSee('Website setup')
            ->assertDontSee('Start domain setup')
            ->assertDontSee('Create template');

        Domain::query()->create([
            'host' => 'www.example.test',
            'name' => 'Example',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('dashboard-module-index', false)
            ->assertSee('dashboard-widget-column', false)
            ->assertDontSee('Website setup')
            ->assertDontSee('Start domain setup')
            ->assertDontSee('Create template');

        Domain::query()->delete();

        WebsiteTemplate::query()->create([
            'handle' => 'base',
            'name' => 'Base Template',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('dashboard-module-index', false)
            ->assertSee('dashboard-widget-column', false)
            ->assertDontSee('Website setup')
            ->assertDontSee('Start domain setup')
            ->assertDontSee('Create template');

        Domain::query()->create([
            'host' => 'www.ready.test',
            'name' => 'Ready',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Website setup')
            ->assertDontSee('Start domain setup')
            ->assertDontSee('Create template')
            ->assertSee('dashboard-module-index', false)
            ->assertSee('dashboard-widget-column', false);
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
                'settings' => [
                    'seo' => [
                        'locales' => [
                            'nl' => [
                                'default_meta_title' => 'Nederlandse fallback titel',
                                'default_meta_description' => 'Nederlandse fallback description.',
                            ],
                            'fr' => [
                                'default_meta_title' => 'French fallback title',
                                'default_meta_description' => 'French fallback description.',
                                'default_og_title' => 'French social title',
                            ],
                        ],
                    ],
                ],
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
        $this->assertSame('Nederlandse fallback titel', $domain->settings['seo']['locales']['nl']['default_meta_title']);
        $this->assertSame('French social title', $domain->settings['seo']['locales']['fr']['default_og_title']);
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
            ->get(route('admin.domains.edit.step', ['domain' => $domain, 'step' => 'languages']))
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
            ->assertRedirect(route('admin.domains.edit.step', ['domain' => $domain, 'step' => 'template']));

        $german = CmsLanguage::query()->where('code', 'de')->firstOrFail();
        $domain->refresh();

        $this->assertTrue($german->is_enabled);
        $this->assertSame('active', $german->status);
        $this->assertSame(['nl', 'de'], $domain->active_frontend_locales);
        $this->assertSame(['nl', 'de'], $domain->active_backend_locales);
    }

    public function test_admin_can_require_two_factor_for_domain_backend_login(): void
    {
        $admin = User::factory()->admin()->create();
        $domain = Domain::query()->create([
            'host' => 'secure.example.test',
            'name' => 'Secure Example',
            'default_locale' => 'nl',
            'active_frontend_locales' => ['nl'],
            'active_backend_locales' => ['nl'],
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.domains.edit.step', ['domain' => $domain, 'step' => 'security']))
            ->assertOk()
            ->assertSee('Tweefactorauthenticatie')
            ->assertSee('2FA verplichten voor CMS-login');

        $this->actingAs($admin)
            ->put(route('admin.domains.update', $domain), [
                '_domain_step' => 'security',
                '_next_step' => 'social-contact',
                'settings' => [
                    'security' => [
                        'backend_two_factor_required' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.domains.edit.step', ['domain' => $domain, 'step' => 'social-contact']));

        $this->assertTrue($domain->refresh()->requiresTwoFactorForBackend());
    }

    public function test_domain_template_seeder_creates_local_and_sample_domains(): void
    {
        $this->seed(DomainTemplateSeeder::class);

        $this->assertDatabaseHas('domains', [
            'host' => 'base-cms.test',
            'is_development' => true,
        ]);
        $this->assertDatabaseHas('domains', ['host' => 'www.example.nl']);
        $this->assertDatabaseHas('domains', ['host' => 'www.example.com']);
        $this->assertDatabaseHas('domains', ['host' => 'www.example.fr']);
        $this->assertDatabaseHas('website_templates', [
            'handle' => 'default',
            'stylesheet_path' => 'resources/scss/site/templates/default/_index.scss',
            'asset_path' => 'public/site/templates/default',
        ]);

        $template = WebsiteTemplate::query()->where('handle', 'default')->firstOrFail();

        $this->assertFalse($template->default_settings['show_footer_credit']);
        $this->assertArrayNotHasKey('footer_credit_label', $template->default_settings);
        $this->assertArrayNotHasKey('footer_credit_url', $template->default_settings);
        $this->assertSame('header_top', $template->usp_sets[0]['location']);
        $this->assertSame('done', $template->usp_sets[0]['items'][0]['icon']);
        $this->assertSame('footer_top', $template->usp_sets[1]['location']);
    }

    public function test_template_section_editor_saves_sections_interactively(): void
    {
        $admin = User::factory()->admin()->create();
        $template = WebsiteTemplate::query()->create([
            'handle' => 'interactive',
            'name' => 'Interactive Template',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(TemplateSectionEditor::class, ['template' => $template])
            ->set('sections.0.label', 'Homepage hero')
            ->set('sections.0.handle', 'homepage_hero')
            ->set('sections.0.type', 'banner')
            ->call('addSection')
            ->set('sections.2.label', 'Footer banner')
            ->set('sections.2.handle', 'footer_banner')
            ->set('sections.2.type', 'mixed')
            ->call('moveSection', 2, 'up')
            ->call('save')
            ->assertSet('message', 'Template secties opgeslagen.');

        $template->refresh();

        $this->assertSame('homepage_hero', $template->defined_sections[0]['handle']);
        $this->assertSame('footer_banner', $template->defined_sections[1]['handle']);
        $this->assertSame('mixed', $template->defined_sections[1]['type']);
        $this->assertSame($admin->id, $template->updated_by);
    }

    public function test_template_usp_set_editor_saves_sets_interactively(): void
    {
        $admin = User::factory()->admin()->create();
        $template = WebsiteTemplate::query()->create([
            'handle' => 'usps',
            'name' => 'USP Template',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(TemplateUspSetEditor::class, ['template' => $template])
            ->set('sets.0.name', 'Header benefits')
            ->set('sets.0.location', 'header_top')
            ->set('sets.0.items.0.label', 'SEO vriendelijke basis')
            ->set('sets.0.items.0.icon', '')
            ->call('addItem', 0)
            ->set('sets.0.items.1.label', 'Veilig beheer')
            ->set('sets.0.items.1.icon', 'verified_user')
            ->call('addSet')
            ->set('sets.1.name', 'Footer benefits')
            ->set('sets.1.location', 'footer_top')
            ->set('sets.1.items.0.label', 'Meertalige content')
            ->set('sets.1.items.0.icon', 'translate')
            ->call('moveItem', 0, 1, 'up')
            ->call('save')
            ->assertSet('message', 'USP sets opgeslagen.');

        $template->refresh();

        $this->assertSame('Header benefits', $template->usp_sets[0]['name']);
        $this->assertSame('Veilig beheer', $template->usp_sets[0]['items'][0]['label']);
        $this->assertSame('verified_user', $template->usp_sets[0]['items'][0]['icon']);
        $this->assertSame('done', $template->usp_sets[0]['items'][1]['icon']);
        $this->assertSame('footer_top', $template->usp_sets[1]['location']);
        $this->assertSame($admin->id, $template->updated_by);
    }
}
