<?php

namespace Tests\Feature\Admin;

use App\Models\Cms\CmsRedirect;
use App\Models\Cms\ContentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_publish_status_from_overview_picker(): void
    {
        $admin = User::factory()->admin()->create();
        $contentItem = ContentItem::query()->create([
            'title' => 'Quick status page',
            'slug' => 'quick-status-page',
            'locale' => 'nl',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->from('/admin/content')
            ->patch(route('admin.quick-status.update'), [
                'model' => 'content',
                'id' => $contentItem->id,
                'status' => 'draft',
            ])
            ->assertRedirect('/admin/content')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('content_items', [
            'id' => $contentItem->id,
            'status' => 'draft',
            'updated_by' => $admin->id,
        ]);
    }

    public function test_admin_can_change_boolean_status_from_overview_picker(): void
    {
        $admin = User::factory()->admin()->create();
        $redirect = CmsRedirect::query()->create([
            'source_path' => 'old-page',
            'target_url' => '/new-page',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.quick-status.update'), [
                'model' => 'redirect',
                'id' => $redirect->id,
                'status' => 'inactive',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('redirects', [
            'id' => $redirect->id,
            'is_active' => false,
        ]);
    }

    public function test_invalid_quick_status_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $contentItem = ContentItem::query()->create([
            'title' => 'Protected quick status page',
            'slug' => 'protected-quick-status-page',
            'locale' => 'nl',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->from('/admin/content')
            ->patch(route('admin.quick-status.update'), [
                'model' => 'content',
                'id' => $contentItem->id,
                'status' => 'inactive',
            ])
            ->assertRedirect('/admin/content')
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('content_items', [
            'id' => $contentItem->id,
            'status' => 'published',
        ]);
    }

    public function test_overview_renders_clickable_status_picker(): void
    {
        $admin = User::factory()->admin()->create();

        ContentItem::query()->create([
            'title' => 'Clickable status page',
            'slug' => 'clickable-status-page',
            'locale' => 'nl',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get('/admin/content')
            ->assertOk()
            ->assertSee('quick-status-trigger', false)
            ->assertSee('quick-status-backdrop', false)
            ->assertSee('data-quick-status-close', false)
            ->assertSee('Beschikbare statussen')
            ->assertSee('name="model" value="content"', false);
    }
}
