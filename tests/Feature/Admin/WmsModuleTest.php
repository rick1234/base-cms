<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Wms\ContentItem;
use App\Models\Wms\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class WmsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_translated_wms_module_tables_exist(): void
    {
        foreach (config('wms.modules') as $module) {
            $this->assertTrue(
                Schema::hasTable($module['table']),
                "Missing translated WMS table [{$module['table']}].",
            );
        }

        $this->assertTrue(Schema::hasTable('wms_events'));
        $this->assertTrue(Schema::hasTable('wms_locations'));
        $this->assertTrue(Schema::hasTable('wms_content_items'));
        $this->assertTrue(Schema::hasTable('wms_catalog_products'));
    }

    public function test_admin_can_browse_translated_wms_modules(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/wms/index.php')
            ->assertOk()
            ->assertSee('Content')
            ->assertSee('Events')
            ->assertSee('Locations')
            ->assertSee('Catalog')
            ->assertSee('Roles and Permissions');
    }

    public function test_every_configured_legacy_wms_index_route_resolves(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (config('wms.screens') as $screen) {
            $path = Str::after($screen['legacy_path'], 'wms/');

            $this->actingAs($admin)
                ->get("/wms/{$path}/index.php")
                ->assertOk()
                ->assertSee($screen['name'])
                ->assertSee($screen['legacy_path']);
        }
    }

    public function test_every_configured_legacy_wms_page_route_resolves(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (config('wms.screens') as $screen) {
            $path = Str::after($screen['legacy_path'], 'wms/');

            foreach ($screen['pages'] as $page => $definition) {
                if ($page === 'index') {
                    continue;
                }

                $this->actingAs($admin)
                    ->get("/wms/{$path}/{$page}.php")
                    ->assertOk()
                    ->assertSee($screen['legacy_path']);
            }
        }
    }

    public function test_legacy_root_utility_pages_resolve(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['firstLogin.php', 'resetpass.php', 'token.php', 'delete.php'] as $page) {
            $this->actingAs($admin)
                ->get("/wms/{$page}")
                ->assertOk();
        }
    }

    public function test_old_folder_based_wms_routes_resolve_to_translated_modules(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/wms/content/index.php')
            ->assertOk()
            ->assertSee('Content')
            ->assertSee('contentitems');

        $this->actingAs($admin)
            ->get('/wms/evenementen/index.php')
            ->assertOk()
            ->assertSee('Events')
            ->assertSee('evenement');

        $this->actingAs($admin)
            ->get('/wms/vestigingen/index.php')
            ->assertOk()
            ->assertSee('Locations')
            ->assertSee('vestiging');

        $event = Event::query()->create([
            'title' => 'Folder edit event',
            'slug' => 'folder-edit-event',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get("/wms/evenementen/edit.php?id={$event->id}")
            ->assertOk()
            ->assertSee('Edit Events')
            ->assertSee('Folder edit event');
    }

    public function test_nested_legacy_category_screen_has_crud_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/wms/content/categorieen/index.php', [
                'name' => 'Legacy category',
                'slug' => 'legacy-category',
                'status' => 'active',
            ])
            ->assertRedirect('/wms/content/categorieen/edit.php?id=1');

        $this->assertDatabaseHas('wms_content_categories', [
            'name' => 'Legacy category',
            'slug' => 'legacy-category',
        ]);

        $this->actingAs($admin)
            ->post('/wms/content/categorieen/edit.php?id=1', [
                'name' => 'Updated category',
                'slug' => 'legacy-category',
                'status' => 'active',
            ])
            ->assertRedirect('/wms/content/categorieen/edit.php?id=1');

        $this->assertDatabaseHas('wms_content_categories', [
            'id' => 1,
            'name' => 'Updated category',
        ]);

        $this->actingAs($admin)
            ->delete('/wms/content/categorieen/1')
            ->assertRedirect('/wms/content/categorieen/index.php');

        $this->assertSoftDeleted('wms_content_categories', [
            'id' => 1,
        ]);
    }

    public function test_legacy_delete_php_understands_old_object_class_links(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/wms/content/categorieen/index.php', [
                'name' => 'Delete through old link',
                'slug' => 'delete-through-old-link',
                'status' => 'active',
            ])
            ->assertRedirect('/wms/content/categorieen/edit.php?id=1');

        $this->actingAs($admin)
            ->get('/wms/delete.php?obj_id=1&obj_class=ContentCategorie')
            ->assertRedirect('/wms/content/categorieen/index.php');

        $this->assertSoftDeleted('wms_content_categories', [
            'id' => 1,
        ]);
    }

    public function test_admin_can_open_a_wms_module_and_see_records(): void
    {
        $admin = User::factory()->admin()->create();

        ContentItem::query()->create([
            'title' => 'Legacy content translated',
            'slug' => 'legacy-content-translated',
            'locale' => 'en',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get('/wms/content/index.php')
            ->assertOk()
            ->assertSee('wms_content_items')
            ->assertSee('contentitems')
            ->assertSee('Legacy content translated');
    }

    public function test_admin_can_create_and_update_a_translated_wms_record(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/wms/evenementen/index.php', [
                'title' => 'Translated event',
                'slug' => 'translated-event',
                'locale' => 'en',
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('wms_events', [
            'title' => 'Translated event',
            'slug' => 'translated-event',
        ]);

        $eventId = Event::query()->where('slug', 'translated-event')->value('id');

        $this->actingAs($admin)
            ->put("/wms/evenementen/{$eventId}", [
                'title' => 'Updated translated event',
                'slug' => 'translated-event',
                'locale' => 'en',
                'status' => 'published',
            ])
            ->assertRedirect("/wms/evenementen/edit.php?id={$eventId}");

        $this->assertDatabaseHas('wms_events', [
            'id' => $eventId,
            'title' => 'Updated translated event',
        ]);
    }
}
