<?php

namespace Tests\Feature\Admin;

use App\Models\Cms\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/pages', [
                'title' => 'Migration notes',
                'slug' => 'migration-notes',
                'body' => 'Documented page content.',
                'template' => 'default',
                'status' => 'published',
                'sort_order' => 10,
                'published_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect('/admin/pages/migration-notes/edit');

        $this->assertDatabaseHas('cms_pages', [
            'slug' => 'migration-notes',
            'title' => 'Migration notes',
            'status' => 'published',
        ]);
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
        Page::factory()->create([
            'slug' => 'existing-page',
        ]);

        $this->actingAs($admin)
            ->put('/admin/pages/existing-page', [
                'title' => 'Updated title',
                'slug' => 'existing-page',
                'body' => 'Updated body.',
                'template' => 'default',
                'status' => 'published',
                'sort_order' => 1,
            ])
            ->assertRedirect('/admin/pages/existing-page/edit');

        $this->assertDatabaseHas('cms_pages', [
            'slug' => 'existing-page',
            'title' => 'Updated title',
        ]);
    }
}
