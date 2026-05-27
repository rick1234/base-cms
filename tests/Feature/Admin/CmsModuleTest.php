<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Event;
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
            ->assertSee('Content')
            ->assertSee('Events')
            ->assertSee('Locations')
            ->assertSee('Catalog')
            ->assertSee('Roles and Permissions');
    }

    public function test_every_configured_admin_index_route_resolves_without_php_filename(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (config('cms_modules.screens') as $screen) {
            $path = Str::after($screen['legacy_path'], 'cms/');

            $this->actingAs($admin)
                ->get("/admin/{$path}")
                ->assertOk()
                ->assertSee($screen['name']);
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
                    ->assertSee($definition['name'] ?? $screen['name']);
            }
        }
    }

    public function test_old_cms_folder_routes_still_resolve_as_compatibility_aliases(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/cms/content/index.php')
            ->assertOk()
            ->assertSee('Content');
    }

    public function test_admin_folder_routes_match_legacy_content_examples(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/content')
            ->assertOk()
            ->assertSee('Content');

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
