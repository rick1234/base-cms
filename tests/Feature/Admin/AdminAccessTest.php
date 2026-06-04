<?php

namespace Tests\Feature\Admin;

use App\Models\Cms\ContentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_non_admin_user_cannot_enter_admin_area(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_enter_admin_area(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('dashboard-layout', false)
            ->assertSee('dashboard-module-index', false)
            ->assertSee('dashboard-widget-column', false)
            ->assertSee('Ingelogd als')
            ->assertSee('Laatst bekeken')
            ->assertSee('Laatst bewerkt')
            ->assertSee('Laatst toegevoegd')
            ->assertSee(route('admin.users.edit', ['id' => $admin->id]), false)
            ->assertSee(asset('admin/cms/img/favicons/favicon.svg'), false)
            ->assertSee(asset('admin/cms/img/favicons/favicon.ico'), false)
            ->assertDontSee('Search keyword overview')
            ->assertDontSee('Search Keywords')
            ->assertDontSee('Radio')
            ->assertDontSee('Mailing contact overview')
            ->assertDontSee('Mailing Contacts');

        $moduleIndex = Str::between($response->getContent(), '<ul class="dashboard-module-index">', '</ul>');

        $this->assertStringContainsString('/admin/navigation', $moduleIndex);
        $this->assertStringContainsString('/admin/content', $moduleIndex);
        $this->assertStringContainsString('/admin/form', $moduleIndex);
        $this->assertStringContainsString('/admin/evenementen', $moduleIndex);
        $this->assertStringContainsString('/admin/download', $moduleIndex);
        $this->assertStringContainsString('/admin/vestigingen', $moduleIndex);
        $this->assertStringNotContainsString('dashboard-group-', $response->getContent());
        $this->assertStringNotContainsString('Page category overview', $moduleIndex);
        $this->assertStringNotContainsString('Event category overview', $moduleIndex);

        $this->assertLessThan(strpos($moduleIndex, '/admin/content'), strpos($moduleIndex, '/admin/navigation'));
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticated();
    }

    public function test_dashboard_shows_recently_viewed_admin_items(): void
    {
        $admin = User::factory()->admin()->create();
        $contentItem = ContentItem::query()->create([
            'title' => 'Recently viewed dashboard item',
            'slug' => 'recently-viewed-dashboard-item',
            'locale' => 'nl',
            'status' => 'draft',
        ]);

        $this->actingAs($admin);

        $this->get("/admin/content/{$contentItem->id}/edit")
            ->assertOk();

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Laatst bekeken')
            ->assertSee('Recently viewed dashboard item')
            ->assertSee("/admin/content/{$contentItem->id}/edit", false);
    }

    public function test_dashboard_keeps_recently_added_and_edited_items_separate(): void
    {
        $admin = User::factory()->admin()->create();
        $freshItem = ContentItem::query()->create([
            'title' => 'Freshly added dashboard item',
            'slug' => 'freshly-added-dashboard-item',
            'locale' => 'nl',
            'status' => 'draft',
        ]);
        $editedItem = ContentItem::query()->create([
            'title' => 'Recently edited dashboard item',
            'slug' => 'recently-edited-dashboard-item',
            'locale' => 'nl',
            'status' => 'draft',
        ]);

        ContentItem::withoutTimestamps(function () use ($editedItem): void {
            $editedItem
                ->forceFill([
                    'created_at' => now()->subDays(2),
                    'updated_at' => now(),
                ])
                ->save();
        });

        $response = $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();

        $content = $response->getContent();
        $editedWidget = Str::betweenFirst($content, 'id="dashboard-widget-laatst-bewerkt"', '</section>');
        $addedWidget = Str::betweenFirst($content, 'id="dashboard-widget-laatst-toegevoegd"', '</section>');

        $this->assertStringContainsString('Recently edited dashboard item', $editedWidget);
        $this->assertStringNotContainsString($freshItem->title, $editedWidget);
        $this->assertStringContainsString($freshItem->title, $addedWidget);
    }
}
