<?php

namespace Tests\Feature\Frontend;

use App\Models\Cms\ContentItem;
use App\Models\Cms\CmsLanguage;
use App\Models\Cms\Domain;
use App\Models\Cms\NavigationMenu;
use App\Models\Cms\NavigationMenuItem;
use App\Models\Cms\Page;
use Database\Seeders\CountryLanguageSeeder;
use Database\Seeders\TranslationModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PageRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_without_seeded_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Base CMS')
            ->assertSee(asset('favicon.svg'), false)
            ->assertSee(asset('favicon.ico'), false)
            ->assertDontSee('HPU internet services')
            ->assertDontSee('hpu.nl');
    }

    public function test_frontend_language_switcher_uses_flags_instead_of_language_codes(): void
    {
        $this->seed(CountryLanguageSeeder::class);
        $this->seed(TranslationModuleSeeder::class);

        CmsLanguage::query()->updateOrCreate(
            ['code' => 'fr'],
            [
                'name' => 'French',
                'slug' => 'french',
                'native_name' => 'français',
                'direction' => 'ltr',
                'status' => 'active',
                'is_enabled' => true,
                'is_default' => false,
            ],
        );

        $response = $this->get('/')
            ->assertOk()
            ->assertSee('language-widget', false)
            ->assertSee('data-language-modal-trigger', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('Kies taal')
            ->assertSee('Nederlands')
            ->assertSee('English')
            ->assertSee('vendor/flag-icons/flags/4x3/nl.svg', false)
            ->assertSee('vendor/flag-icons/flags/4x3/gb.svg', false);

        $this->assertStringNotContainsString('>NL<', $response->getContent());
        $this->assertStringNotContainsString('>EN<', $response->getContent());
        $this->assertStringNotContainsString('language-listing', $response->getContent());

        $frenchResponse = $this->get('/fr')
            ->assertOk()
            ->assertSee('Choisir la langue')
            ->assertSee('Français')
            ->assertSee('Langue actuelle')
            ->assertDontSee('Current language');

        $this->assertStringNotContainsString('>français<', $frenchResponse->getContent());
    }

    public function test_frontend_navigation_switches_with_the_active_language(): void
    {
        $this->seed(CountryLanguageSeeder::class);
        $this->seed(TranslationModuleSeeder::class);

        CmsLanguage::query()->updateOrCreate(
            ['code' => 'fr'],
            [
                'name' => 'French',
                'slug' => 'french',
                'native_name' => 'franÃ§ais',
                'direction' => 'ltr',
                'status' => 'active',
                'is_enabled' => true,
                'is_default' => false,
            ],
        );

        $dutchMenu = NavigationMenu::query()->create([
            'name' => 'Nederlandse navigatie',
            'handle' => 'primary',
            'locale' => 'nl',
            'is_active' => true,
        ]);
        NavigationMenuItem::query()->create([
            'navigation_menu_id' => $dutchMenu->id,
            'title' => 'Nederlandse link',
            'link_type' => 'custom',
            'custom_url' => '/nl-link',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $frenchMenu = NavigationMenu::query()->create([
            'name' => 'Navigation francaise',
            'handle' => 'primary',
            'locale' => 'fr',
            'is_active' => true,
        ]);
        NavigationMenuItem::query()->create([
            'navigation_menu_id' => $frenchMenu->id,
            'title' => 'Lien francais',
            'link_type' => 'custom',
            'custom_url' => '/fr-link',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Nederlandse link')
            ->assertDontSee('Lien francais');

        $this->get('/fr')
            ->assertOk()
            ->assertSee('Lien francais')
            ->assertDontSee('Nederlandse link');
    }

    public function test_domain_favicon_assets_do_not_get_overridden_by_default_favicon(): void
    {
        Domain::query()->create([
            'host' => 'site.test',
            'name' => 'Site',
            'is_active' => true,
            'is_development' => true,
            'favicon_assets' => [
                'icon' => 'domains/site/favicons/favicon.ico',
                'icon_16' => 'domains/site/favicons/favicon-16x16.png',
                'icon_32' => 'domains/site/favicons/favicon-32x32.png',
                'apple_touch_icon' => 'domains/site/favicons/apple-touch-icon.png',
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee(asset('storage/domains/site/favicons/favicon.ico'), false)
            ->assertDontSee(asset('favicon.svg'), false);
    }

    public function test_frontend_feedback_messages_render_through_closable_flash_component(): void
    {
        $this->withSession([
            'flash_notification' => [
                [
                    'message' => 'Frontend saved.',
                    'level' => 'success',
                    'title' => null,
                    'overlay' => false,
                    'important' => false,
                ],
            ],
        ])
            ->get('/')
            ->assertOk()
            ->assertSee('Frontend saved.')
            ->assertSee('data-flash-message', false)
            ->assertSee('data-flash-close', false);
    }

    public function test_common_laravel_session_status_messages_render_as_flash_messages(): void
    {
        $this->withSession(['status' => 'Profile updated.'])
            ->get('/')
            ->assertOk()
            ->assertSee('Profile updated.')
            ->assertSee('flash-message-success', false)
            ->assertSee('data-flash-close', false);
    }

    public function test_published_page_renders_with_seo_metadata(): void
    {
        Page::factory()->create([
            'slug' => 'about',
            'title' => 'About the base',
            'body' => 'Reusable content lives here.',
            'meta_title' => 'About meta title',
            'meta_description' => 'About meta description for search results.',
        ]);

        $this->get('/about')
            ->assertOk()
            ->assertSee('About meta title')
            ->assertSee('About meta description for search results.')
            ->assertSee('Reusable content lives here.');
    }

    public function test_draft_page_is_not_publicly_available(): void
    {
        Page::factory()->draft()->create([
            'slug' => 'draft-page',
        ]);

        $this->get('/draft-page')->assertNotFound();
    }

    public function test_custom_404_page_renders_with_frontend_layout_and_noindex_metadata(): void
    {
        $this->get('/missing-frontend-page')
            ->assertNotFound()
            ->assertSee('not-found-page', false)
            ->assertSee('Page not found')
            ->assertSee('Go to homepage')
            ->assertSee('Search this website')
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertSee('header-container', false)
            ->assertSee('footer-container', false);
    }

    public function test_published_content_item_renders_from_public_slug(): void
    {
        ContentItem::query()->create([
            'slug' => 'public-content-item',
            'title' => 'Public content item',
            'subtitle' => 'Useful subtitle',
            'structured_blocks' => [
                [
                    'type' => 'text',
                    'uuid' => 'page-rendering-text-block',
                    'layout' => '100',
                    'data' => [
                        'content' => 'Reusable structured content lives here.',
                    ],
                    'settings' => [
                        'alignment' => 'left',
                        'background_style' => 'none',
                        'intro_style' => false,
                    ],
                ],
            ],
            'locale' => 'nl',
            'status' => 'published',
        ]);

        $this->get('/public-content-item')
            ->assertOk()
            ->assertSee('Public content item')
            ->assertSee('Useful subtitle')
            ->assertSee('Reusable structured content lives here.');
    }

    public function test_content_items_do_not_keep_hidden_legacy_intro_body_columns(): void
    {
        $this->assertFalse(Schema::hasColumn('content_items', 'intro'));
        $this->assertFalse(Schema::hasColumn('content_items', 'body'));
    }
}
