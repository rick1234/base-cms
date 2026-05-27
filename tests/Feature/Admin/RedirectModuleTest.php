<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Cms\CmsRedirect;
use Database\Seeders\RedirectModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_module_seeder_creates_demo_redirects_without_duplicates(): void
    {
        $this->seed(RedirectModuleSeeder::class);
        $this->seed(RedirectModuleSeeder::class);

        $this->assertSame(2, CmsRedirect::query()->count());
        $this->assertDatabaseHas('redirects', [
            'source_path' => 'old-base-cms',
            'target_url' => '/',
            'status_code' => 301,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('redirects', [
            'source_path' => 'temporary-campaign',
            'status_code' => 302,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_create_redirect_with_legacy_fields_and_useful_status_code(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/redirect/edit', [
                'old_link' => 'https://example.com/old-path',
                'link' => '/new-path',
                'description' => 'Legacy redirect entry.',
                'status_code' => 308,
                'is_active' => 1,
                'preserve_query' => 1,
            ])
            ->assertRedirect('/admin/redirect/1/edit');

        $this->assertDatabaseHas('redirects', [
            'source_path' => 'old-path',
            'target_url' => '/new-path',
            'description' => 'Legacy redirect entry.',
            'status_code' => 308,
            'is_active' => true,
            'preserve_query' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/redirect')
            ->assertOk()
            ->assertSee('Redirect overview')
            ->assertSee('/old-path')
            ->assertSee('/new-path');
    }

    public function test_frontend_redirects_single_segment_and_fallback_paths_with_query_preservation(): void
    {
        CmsRedirect::query()->create([
            'source_path' => 'old-page',
            'target_url' => '/new-page',
            'status_code' => 307,
            'is_active' => true,
            'preserve_query' => true,
        ]);
        CmsRedirect::query()->create([
            'source_path' => 'legacy/deep/path',
            'target_url' => 'https://example.com/deep-target',
            'status_code' => 302,
            'is_active' => true,
            'preserve_query' => false,
        ]);

        $this->get('/old-page?utm=abc')
            ->assertStatus(307)
            ->assertHeader('Location', 'http://localhost/new-page?utm=abc');

        $this->get('/legacy/deep/path?utm=abc')
            ->assertStatus(302)
            ->assertHeader('Location', 'https://example.com/deep-target');

        $this->assertDatabaseHas('redirects', [
            'source_path' => 'old-page',
            'hit_count' => 1,
        ]);
        $this->assertNotNull(CmsRedirect::query()->where('source_path', 'old-page')->value('last_used_at'));
    }

    public function test_inactive_redirect_does_not_resolve(): void
    {
        CmsRedirect::query()->create([
            'source_path' => 'inactive-redirect',
            'target_url' => '/target',
            'status_code' => 301,
            'is_active' => false,
        ]);

        $this->get('/inactive-redirect')->assertNotFound();
    }

    public function test_redirect_inline_update_and_delete_routes_support_legacy_ajax_names(): void
    {
        $admin = User::factory()->admin()->create();
        $redirect = CmsRedirect::query()->create([
            'source_path' => 'old-inline-source',
            'target_url' => '/target',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/redirect/ajax/editRedirect', [
                'redirectid' => $redirect->id,
                'newValue' => '/new-inline-source',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('source_path', 'new-inline-source');

        $this->assertDatabaseHas('redirects', [
            'id' => $redirect->id,
            'source_path' => 'new-inline-source',
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/redirect/ajax/deleteRedirect', [
                'redirectid' => $redirect->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('redirects', [
            'id' => $redirect->id,
        ]);
    }
}
