<?php

namespace Tests\Feature\Database;

use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogProductTranslation;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Domain;
use App\Models\Cms\Download;
use App\Models\Cms\DownloadAccessToken;
use App\Models\Cms\Event;
use App\Models\Cms\FaqItem;
use App\Models\Cms\Form;
use App\Models\Cms\Location;
use App\Models\Cms\NavigationMenu;
use App\Models\Cms\NavigationMenuItem;
use App\Models\Cms\Page;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoWebsiteSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_builds_a_realistic_demo_website(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(15, Page::query()->whereNull('domain_id')->where('status', 'published')->count());
        $this->assertGreaterThanOrEqual(6, ContentItem::query()->where('status', 'published')->count());
        $this->assertGreaterThanOrEqual(6, CatalogProduct::query()->where('status', 'published')->count());
        $this->assertGreaterThanOrEqual(4, Event::query()->where('status', 'published')->count());
        $this->assertGreaterThanOrEqual(6, Form::query()->where('status', 'published')->count());
        $this->assertGreaterThanOrEqual(4, Download::query()->where('status', 'active')->count());
        $this->assertGreaterThanOrEqual(3, Location::query()->where('status', 'active')->count());
        $this->assertGreaterThanOrEqual(8, FaqItem::query()->where('status', 'published')->count());

        $this->assertDatabaseHas('forms', ['slug' => 'quote-request', 'locale' => 'nl']);
        $this->assertDatabaseHas('forms', ['slug' => 'quote-request-en', 'locale' => 'en']);
        $this->assertDatabaseHas('content_items', ['slug' => 'website-redesign-checklist', 'locale' => 'nl']);
        $this->assertDatabaseHas('content_items', ['slug' => 'website-redesign-checklist-en', 'locale' => 'en']);
        $this->assertDatabaseHas('events', ['slug' => 'cms-planning-workshop', 'locale' => 'nl']);
        $this->assertDatabaseHas('events', ['slug' => 'cms-planning-workshop-en', 'locale' => 'en']);
        $this->assertDatabaseHas('faq_items', ['slug' => 'how-long-does-a-demo-project-take', 'locale' => 'nl']);
        $this->assertDatabaseHas('faq_items', ['slug' => 'how-long-does-a-demo-project-take-en', 'locale' => 'en']);
        $this->assertGreaterThanOrEqual(2, CatalogProductTranslation::query()->where('locale', 'nl')->count());
        $this->assertGreaterThanOrEqual(2, CatalogProductTranslation::query()->where('locale', 'en')->count());

        $primaryMenu = NavigationMenu::query()->where('handle', 'primary')->whereNull('domain_id')->firstOrFail();
        $footerMenu = NavigationMenu::query()->where('handle', 'footer')->whereNull('domain_id')->firstOrFail();
        $products = NavigationMenuItem::query()->where('navigation_menu_id', $primaryMenu->id)->where('title', 'Producten')->firstOrFail();
        $clientPortal = NavigationMenuItem::query()->where('navigation_menu_id', $primaryMenu->id)->where('title', 'Klantportaal')->firstOrFail();
        $draftCampaign = NavigationMenuItem::query()->where('navigation_menu_id', $primaryMenu->id)->where('title', 'Conceptcampagne')->firstOrFail();
        $footerPrivacy = NavigationMenuItem::query()->where('navigation_menu_id', $footerMenu->id)->where('title', 'Privacy')->firstOrFail();

        $this->assertSame('Hoofdnavigatie', $primaryMenu->name);
        $this->assertSame('Voeternavigatie', $footerMenu->name);
        $this->assertSame('catalog_category', $products->link_type);
        $this->assertTrue($products->expand_children);
        $this->assertSame('https://portal.example.com', $clientPortal->custom_url);
        $this->assertTrue($clientPortal->opens_new_tab);
        $this->assertFalse($draftCampaign->is_active);
        $this->assertSame('page', $footerPrivacy->link_type);
        $this->assertNotSame($primaryMenu->id, $footerMenu->id);

        $this->get('/')
            ->assertOk()
            ->assertSee('Acme Nederland')
            ->assertSee('Diensten')
            ->assertSee('Catalogus')
            ->assertSee('Voeternavigatie')
            ->assertSee('Privacy')
            ->assertDontSee('Conceptcampagne');

        $this->get('/products/software')->assertRedirect('/software');
    }

    public function test_database_seeder_builds_three_language_specific_platform_websites(): void
    {
        $this->seed(DatabaseSeeder::class);

        $sites = [
            'www.example.nl' => ['key' => 'nl', 'locale' => 'nl'],
            'www.example.com' => ['key' => 'en', 'locale' => 'en'],
            'www.example.fr' => ['key' => 'fr', 'locale' => 'fr'],
        ];

        foreach ($sites as $host => $site) {
            $domain = Domain::query()->where('host', $host)->firstOrFail();

            $this->assertSame($site['locale'], $domain->default_locale);
            $this->assertSame([$site['locale']], $domain->active_frontend_locales);

            $this->assertDatabaseHas('cms_pages', [
                'domain_id' => $domain->id,
                'slug' => 'home',
                'status' => 'published',
            ]);
            $this->assertDatabaseHas('content_items', [
                'domain_id' => $domain->id,
                'slug' => 'services',
                'locale' => $site['locale'],
                'status' => 'published',
            ]);
            $this->assertDatabaseHas('events', [
                'domain_id' => $domain->id,
                'slug' => 'editor-demo-day',
                'locale' => $site['locale'],
                'status' => 'published',
            ]);
            $this->assertDatabaseHas('catalog_products', [
                'domain_id' => $domain->id,
                'sku' => strtoupper($site['key']).'-CMS-CARE',
                'status' => 'published',
            ]);
            $this->assertDatabaseHas('locations', [
                'domain_id' => $domain->id,
                'name' => $domain->name.' HQ',
                'status' => 'active',
            ]);
            $this->assertDatabaseHas('vacancies', [
                'domain_id' => $domain->id,
                'slug' => 'cms-specialist',
                'locale' => $site['locale'],
                'status' => 'published',
            ]);
            $this->assertDatabaseHas('faq_items', [
                'domain_id' => $domain->id,
                'slug' => 'content',
                'locale' => $site['locale'],
                'status' => 'published',
            ]);

            $contentItem = ContentItem::query()
                ->where('domain_id', $domain->id)
                ->where('slug', 'services')
                ->firstOrFail();
            $this->assertContains('gallery', collect($contentItem->structured_blocks)->pluck('type')->all());
            $this->assertContains('attachment', collect($contentItem->structured_blocks)->pluck('type')->all());

            $form = Form::query()
                ->where('domain_id', $domain->id)
                ->where('slug', $site['key'].'-contact')
                ->firstOrFail();
            $this->assertTrue($form->blocks()->exists());
            $this->assertTrue($form->recipients()->exists());
            $this->assertTrue($form->messages()->where('type', 'confirmation')->exists());
            $this->assertTrue($form->submissions()->exists());

            $primaryMenu = NavigationMenu::query()
                ->where('domain_id', $domain->id)
                ->where('handle', 'primary')
                ->where('locale', $site['locale'])
                ->firstOrFail();
            $this->assertGreaterThanOrEqual(7, $primaryMenu->items()->count());
        }

        foreach (['nl', 'en', 'fr'] as $key) {
            $this->assertDatabaseHas('downloads', [
                'slug' => 'platform-'.$key.'-public-download',
                'status' => 'active',
            ]);
            $this->assertDatabaseHas('downloads', [
                'slug' => 'platform-'.$key.'-protected-download',
                'is_password_protected' => true,
            ]);
            $this->assertTrue(DownloadAccessToken::query()
                ->where('email', 'download-'.$key.'@example.com')
                ->where('purpose', 'invite')
                ->exists());
        }
    }
}
