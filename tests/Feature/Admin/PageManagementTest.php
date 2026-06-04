<?php

namespace Tests\Feature\Admin;

use App\Models\Cms\CmsRedirect;
use App\Models\Cms\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->post('/admin/pages', [
                'title' => 'Migration notes',
                'slug' => 'migration-notes',
                'body' => 'Documented page content.',
                'template' => 'default',
                'status' => 'published',
                'sort_order' => 10,
                'published_at' => now()->toDateTimeString(),
            ]);

        $page = Page::query()->where('slug', 'migration-notes')->firstOrFail();

        $response->assertRedirect(route('admin.pages.edit', $page));

        $this->assertDatabaseHas('cms_pages', [
            'slug' => 'migration-notes',
            'title' => 'Migration notes',
            'status' => 'published',
        ]);
    }

    public function test_admin_feedback_messages_render_through_closable_flash_component(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->followingRedirects()
            ->post('/admin/pages', [
                'title' => 'Flash notes',
                'slug' => 'flash-notes',
                'body' => 'Documented flash content.',
                'template' => 'default',
                'status' => 'published',
                'sort_order' => 10,
                'published_at' => now()->toDateTimeString(),
            ])
            ->assertOk()
            ->assertSee('Page created.')
            ->assertSee('data-flash-message', false)
            ->assertSee('data-flash-close', false);
    }

    public function test_admin_can_open_page_edit_screen_by_numeric_id(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create([
            'slug' => 'existing-page',
        ]);

        $this->actingAs($admin)
            ->get("/admin/pages/{$page->id}/edit")
            ->assertOk()
            ->assertSee('existing-page');
    }

    public function test_missing_admin_page_returns_not_found_without_frontend_layout_error(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/pages/999999/edit')
            ->assertNotFound()
            ->assertSee('Page not found');
    }

    public function test_admin_pages_breadcrumbs_use_navigation_screen_labels(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get('/admin/pages/create')
            ->assertOk()
            ->assertSee('admin-breadcrumbs-bar', false);

        $breadcrumbs = Str::between($response->getContent(), '<div class="admin-breadcrumbs-bar">', '</div>');

        $homePosition = strpos($breadcrumbs, __('Home'));
        $groupPosition = strpos($breadcrumbs, __('Content'));
        $screenPosition = strpos($breadcrumbs, e(__('Pages')));
        $createPosition = strpos($breadcrumbs, __('Toevoegen'));

        $this->assertIsInt($homePosition);
        $this->assertIsInt($groupPosition);
        $this->assertIsInt($screenPosition);
        $this->assertIsInt($createPosition);
        $this->assertLessThan($groupPosition, $homePosition);
        $this->assertLessThan($screenPosition, $groupPosition);
        $this->assertLessThan($createPosition, $screenPosition);
    }

    public function test_admin_page_validation_rejects_reserved_slugs(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from('/admin/pages/create')
            ->post('/admin/pages', [
                'title' => 'Admin',
                'slug' => 'admin',
                'template' => 'default',
                'status' => 'published',
                'sort_order' => 0,
            ])
            ->assertRedirect('/admin/pages/create')
            ->assertSessionHasErrors('slug');
    }

    public function test_admin_can_update_a_page(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create([
            'slug' => 'existing-page',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.pages.update', $page), [
                'title' => 'Updated title',
                'slug' => 'existing-page',
                'body' => 'Updated body.',
                'template' => 'default',
                'status' => 'published',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.pages.edit', $page));

        $this->assertDatabaseHas('cms_pages', [
            'slug' => 'existing-page',
            'title' => 'Updated title',
        ]);
    }

    public function test_generated_page_slug_follows_title_changes_and_creates_permanent_redirect(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create([
            'title' => 'Fish',
            'slug' => 'fish',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.pages.update', $page), [
                'title' => 'Fishes',
                'slug' => 'fish',
                'body' => 'Updated body.',
                'template' => 'default',
                'status' => 'published',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.pages.edit', $page));

        $this->assertSame('fishes', $page->refresh()->slug);
        $this->assertDatabaseHas('redirects', [
            'source_path' => 'fish',
            'target_url' => '/fishes',
            'status_code' => 301,
            'is_active' => true,
            'preserve_query' => true,
        ]);

        $this->get('/fish?ref=old')
            ->assertStatus(301)
            ->assertHeader('Location', 'http://localhost/fishes?ref=old');
    }

    public function test_page_slug_history_reuses_existing_redirect_rule(): void
    {
        $admin = User::factory()->admin()->create();
        $redirect = CmsRedirect::query()->create([
            'source_path' => 'fish',
            'target_url' => '/legacy-fish',
            'description' => 'Old imported redirect.',
            'status_code' => 302,
            'is_active' => false,
        ]);
        $page = Page::factory()->create([
            'title' => 'Fish',
            'slug' => 'fish',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.pages.update', $page), [
                'title' => 'Fishes',
                'slug' => 'fish',
                'body' => 'Updated body.',
                'template' => 'default',
                'status' => 'published',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.pages.edit', $page));

        $redirect->refresh();

        $this->assertSame('fishes', $page->refresh()->slug);
        $this->assertSame('/fishes', $redirect->target_url);
        $this->assertSame(301, $redirect->status_code);
        $this->assertTrue($redirect->is_active);
        $this->assertStringStartsWith('Slug history:', (string) $redirect->description);
        $this->assertStringContainsString("[page:{$page->id}]", (string) $redirect->description);
    }
}
