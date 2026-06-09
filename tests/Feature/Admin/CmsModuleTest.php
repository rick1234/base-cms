<?php

namespace Tests\Feature\Admin;

use App\Models\Cms\CmsLanguage;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Event;
use App\Models\User;
use Database\Seeders\CountryLanguageSeeder;
use Database\Seeders\TranslationModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CmsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_module_tables_use_laravel_conventions(): void
    {
        foreach (config('cms_modules.modules') as $module) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]+(_[a-z0-9]+)*$/', $module['table']);
            $this->assertTrue(
                Schema::hasTable($module['table']),
                "Missing admin module table [{$module['table']}].",
            );
        }

        $this->assertTrue(Schema::hasTable('events'));
        $this->assertTrue(Schema::hasTable('locations'));
        $this->assertTrue(Schema::hasTable('content_items'));
        $this->assertTrue(Schema::hasTable('catalog_products'));
        $this->assertFalse(Schema::hasTable('cms_events'));
        $this->assertFalse(Schema::hasTable('cms_content_items'));
        $this->assertFalse(Schema::hasTable('radio_guides'));
        $this->assertFalse(Schema::hasTable('radio_programs'));
        $this->assertFalse(Schema::hasTable('radio_schedules'));
        $this->assertFalse(Schema::hasTable('mailing_contacts'));
        $this->assertFalse(Schema::hasTable('mailing_contact_categories'));
        $this->assertFalse(Schema::hasTable('mailings'));
        $this->assertFalse(Schema::hasTable('mailing_items'));
    }

    public function test_admin_can_browse_modules_from_primary_admin_route(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/modules')
            ->assertOk()
            ->assertSee('Admin modules')
            ->assertSee('Pagina\'s')
            ->assertSee('Evenementen')
            ->assertSee('Vestigingen')
            ->assertSee('Catalogus')
            ->assertSee('Rollen en rechten')
            ->assertDontSee('Website setup')
            ->assertDontSee('Open direct het overzicht van een module.');
    }

    public function test_legacy_module_manager_routes_and_database_artifacts_are_removed(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertArrayNotHasKey('module_manager', config('cms_modules.modules'));
        $this->assertArrayNotHasKey('module_manager', config('cms_modules.screens'));
        $this->assertArrayNotHasKey('module_categories', config('cms_modules.screens'));
        $this->assertArrayNotHasKey('Module', config('cms_modules.legacy_classes'));
        $this->assertArrayNotHasKey('ModuleCategorie', config('cms_modules.legacy_classes'));
        $this->assertFalse(Schema::hasTable('module_categories'));
        $this->assertFalse(Schema::hasTable('module_category_module'));

        $this->actingAs($admin)
            ->get('/admin/module')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get('/admin/module/categorieen')
            ->assertNotFound();
    }

    public function test_every_configured_admin_index_route_resolves_without_php_filename(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (config('cms_modules.screens') as $screen) {
            $path = Str::after($screen['legacy_path'], 'cms/');

            $this->actingAs($admin)
                ->get("/admin/{$path}")
                ->assertOk()
                ->assertSee(__(data_get($screen, 'pages.index.name', $screen['name'])));
        }
    }

    public function test_every_configured_admin_page_route_resolves_without_php_extension(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (config('cms_modules.screens') as $screen) {
            $path = Str::after($screen['legacy_path'], 'cms/');

            foreach ($screen['pages'] as $page => $definition) {
                if ($page === 'index') {
                    continue;
                }

                $this->actingAs($admin)
                    ->get("/admin/{$path}/{$page}")
                    ->assertOk()
                    ->assertSee(__($definition['name'] ?? $screen['name']));
            }
        }
    }

    public function test_old_cms_folder_routes_still_resolve_as_compatibility_aliases(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/cms/content/index.php')
            ->assertOk()
            ->assertSee(__('Pages'));
    }

    public function test_removed_slider_legacy_cms_screens_are_not_registered(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/cms/slider/index.php')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get('/cms/slider/categorieen/index.php')
            ->assertNotFound();
    }

    public function test_dedicated_cms_empty_states_use_human_module_names(): void
    {
        $admin = User::factory()->admin()->create();

        $this->seed(CountryLanguageSeeder::class);
        $this->seed(TranslationModuleSeeder::class);

        CmsLanguage::query()->update([
            'is_enabled' => false,
            'is_default' => false,
        ]);

        app()->setLocale('nl');
        app('translator')->setLoaded([]);

        $emptyStates = [
            '/admin/domains' => [
                'expected' => 'Er zijn geen domeinen gevonden.',
                'obsolete' => ['No domains have been configured yet.'],
            ],
            '/admin/templates' => [
                'expected' => 'Er zijn geen templates gevonden.',
                'obsolete' => ['No templates have been configured yet.'],
            ],
            '/admin/navigation' => [
                'expected' => 'Er zijn geen navigatiemenu\'s gevonden.',
                'obsolete' => ['No navigation menus have been created yet.'],
            ],
            '/admin/pages' => [
                'expected' => 'Er zijn geen pagina\'s gevonden.',
                'obsolete' => ['No pages have been created yet.'],
            ],
            '/admin/landen/talen' => [
                'expected' => 'Er zijn geen websitetalen gevonden.',
                'obsolete' => ['No website languages enabled yet.'],
            ],
            '/admin/content/categorieen' => [
                'expected' => 'Er zijn geen categorieen gevonden.',
                'obsolete' => ['Er zijn nog geen categorieen toegevoegd.'],
            ],
        ];

        foreach ($emptyStates as $path => $state) {
            $response = $this->actingAs($admin)
                ->get($path)
                ->assertOk()
                ->assertSee($state['expected']);

            foreach ($state['obsolete'] as $obsoleteText) {
                $response->assertDontSee($obsoleteText);
            }
        }
    }

    public function test_admin_folder_routes_match_legacy_content_examples(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/content')
            ->assertOk()
            ->assertSee(__('Pages'));

        $event = Event::query()->create([
            'title' => 'Folder edit event',
            'slug' => 'folder-edit-event',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get("/admin/evenementen/edit?id={$event->id}")
            ->assertOk()
            ->assertSee('Bewerk:')
            ->assertSee('Folder edit event');
    }

    public function test_nested_admin_category_screen_has_crud_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/content/categorieen', [
                'name' => 'Legacy category',
                'slug' => 'legacy-category',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/content/categorieen/1/edit');

        $this->assertDatabaseHas('content_categories', [
            'name' => 'Legacy category',
            'slug' => 'legacy-category',
        ]);

        $this->actingAs($admin)
            ->post('/admin/content/categorieen/edit?id=1', [
                'name' => 'Updated category',
                'slug' => 'legacy-category',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/content/categorieen/1/edit');

        $this->assertDatabaseHas('content_categories', [
            'id' => 1,
            'name' => 'Updated category',
        ]);

        $this->actingAs($admin)
            ->delete('/admin/content/categorieen/1')
            ->assertRedirect('/admin/content/categorieen');

        $this->assertSoftDeleted('content_categories', [
            'id' => 1,
        ]);
    }

    public function test_admin_delete_route_understands_old_object_class_links(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/content/categorieen', [
                'name' => 'Delete through old link',
                'slug' => 'delete-through-old-link',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/content/categorieen/1/edit');

        $this->actingAs($admin)
            ->get('/admin/delete?obj_id=1&obj_class=ContentCategorie')
            ->assertRedirect('/admin/content/categorieen');

        $this->assertSoftDeleted('content_categories', [
            'id' => 1,
        ]);
    }

    public function test_admin_can_open_a_module_and_see_records(): void
    {
        $admin = User::factory()->admin()->create();

        ContentItem::query()->create([
            'title' => 'Legacy content translated',
            'slug' => 'legacy-content-translated',
            'locale' => 'en',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get('/admin/content')
            ->assertOk()
            ->assertSee('Legacy content translated');
    }

    public function test_admin_can_create_and_update_a_record(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/evenementen', [
                'title' => 'Translated event',
                'slug' => 'translated-event',
                'locale' => 'en',
                'status' => 'published',
            ])
            ->assertRedirect('/admin/evenementen/1/edit');

        $this->assertDatabaseHas('events', [
            'title' => 'Translated event',
            'slug' => 'translated-event',
        ]);

        $eventId = Event::query()->where('slug', 'translated-event')->value('id');

        $this->actingAs($admin)
            ->put("/admin/evenementen/{$eventId}", [
                'title' => 'Updated translated event',
                'slug' => 'translated-event',
                'locale' => 'en',
                'status' => 'published',
            ])
            ->assertRedirect("/admin/evenementen/{$eventId}/edit");

        $this->assertDatabaseHas('events', [
            'id' => $eventId,
            'title' => 'Updated translated event',
        ]);
    }
}
