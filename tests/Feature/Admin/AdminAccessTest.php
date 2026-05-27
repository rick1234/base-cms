<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('dashboard-board', false)
            ->assertSee('Content overview')
            ->assertSee('Event category overview')
            ->assertDontSee('Search keyword overview')
            ->assertDontSee('Search Keywords')
            ->assertDontSee('Radio')
            ->assertDontSee('Mailing contact overview')
            ->assertDontSee('Mailing Contacts');
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
}
