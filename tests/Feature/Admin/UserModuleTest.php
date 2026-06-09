<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Cms\Domain;
use App\Models\Cms\UserCategory;
use App\Models\Cms\CmsRole;
use App\Support\Auth\TwoFactorAuthenticator;
use App\Models\Cms\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class UserModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_browse_laravel_users_in_the_cms_layout(): void
    {
        $admin = User::factory()->admin()->create();
        $category = UserCategory::query()->create([
            'name' => 'Editors',
            'slug' => 'editors',
            'status' => 'active',
        ]);
        $editor = User::factory()->create([
            'name' => 'Content Editor',
            'username' => 'editor',
            'email' => 'editor@example.com',
            'is_active' => true,
        ]);
        $editor->categories()->sync([$category->id]);

        $this->actingAs($admin)
            ->get('/admin/users?username=editor')
            ->assertOk()
            ->assertSee('Gebruikers overzicht')
            ->assertSee('editor')
            ->assertSee('editor@example.com')
            ->assertSee('Editors');
    }

    public function test_admin_user_listing_renders_category_selector_name_last_login_and_revoked_status(): void
    {
        $admin = User::factory()->admin()->create();
        $category = UserCategory::query()->create([
            'name' => 'Reviewers',
            'slug' => 'reviewers',
            'status' => 'active',
        ]);
        $revokedUser = User::factory()->create([
            'name' => 'Revoked Editor',
            'username' => 'revoked-editor',
            'email' => 'revoked@example.com',
            'is_active' => true,
            'active_until' => now()->subDay(),
        ]);
        $revokedUser->categories()->sync([$category->id]);

        DB::table('user_logins')->insert([
            'user_id' => $revokedUser->id,
            'login_date' => '2026-06-05',
            'login_time' => '14:30:00',
            'created_at' => '2026-06-05 14:30:00',
            'updated_at' => '2026-06-05 14:30:00',
        ]);

        $this->actingAs($admin)
            ->get('/admin/users?status=revoked')
            ->assertOk()
            ->assertSee('listing-category-native', false)
            ->assertSee('Alle categorieen')
            ->assertSee('Naam')
            ->assertSee('Laatst ingelogd')
            ->assertSee('05-06-2026 14:30')
            ->assertSee('Ingetrokken')
            ->assertSee('Revoked Editor')
            ->assertSee('Reviewers');
    }

    public function test_admin_can_create_a_user_with_legacy_profile_extensions(): void
    {
        $admin = User::factory()->admin()->create();
        $category = UserCategory::query()->create([
            'name' => 'Customers',
            'slug' => 'customers',
            'status' => 'active',
        ]);
        $role = CmsRole::query()->create([
            'name' => 'Administrator',
            'slug' => 'administrator',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/users/edit', [
                'name' => 'Client Admin',
                'email' => 'client@example.com',
                'username' => 'client-admin',
                'password' => 'secret-password',
                'locale' => 'nl',
                'is_active' => '1',
                'is_admin' => '1',
                'first_name' => 'Client',
                'last_name' => 'Admin',
                'company_name' => 'Client BV',
                'street' => 'Main Street',
                'house_number' => '12',
                'postal_code' => '1000 AA',
                'city' => 'Amsterdam',
                'country_code' => 'NL',
                'phone' => '+31000000000',
                'role_id' => $role->id,
                'categories' => [$category->id],
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'client@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertTrue($user->is_admin);
        $this->assertSame('Client BV', $user->company_name);
        $this->assertSame($role->id, $user->role_id);
        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => $role->id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('user_category_user', [
            'user_id' => $user->id,
            'user_category_id' => $category->id,
        ]);
    }

    public function test_admin_can_update_a_user_without_changing_the_password(): void
    {
        $admin = User::factory()->admin()->create();
        $category = UserCategory::query()->create([
            'name' => 'Members',
            'slug' => 'members',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'member@example.com',
            'password' => Hash::make('keep-this-password'),
            'is_active' => true,
        ]);
        $originalPassword = $user->password;

        $this->actingAs($admin)
            ->post('/admin/users/edit?id='.$user->id, [
                'id' => $user->id,
                'name' => 'Updated Name',
                'email' => 'member@example.com',
                'username' => 'member',
                'password' => '',
                'locale' => 'en',
                'is_active' => '0',
                'is_admin' => '0',
                'categories' => [$category->id],
            ])
            ->assertRedirect('/admin/users/'.$user->id.'/edit');

        $user->refresh();

        $this->assertSame($originalPassword, $user->password);
        $this->assertSame('Updated Name', $user->name);
        $this->assertFalse($user->is_active);
        $this->assertDatabaseHas('user_category_user', [
            'user_id' => $user->id,
            'user_category_id' => $category->id,
        ]);
    }

    public function test_user_edit_page_uses_tabs_country_picker_and_category_in_profile_block(): void
    {
        $admin = User::factory()->admin()->create();
        $category = UserCategory::query()->create([
            'name' => 'Members',
            'slug' => 'members',
            'status' => 'active',
        ]);
        Country::query()->create([
            'name' => 'Netherlands',
            'slug' => 'netherlands',
            'status' => 'active',
            'iso2' => 'NL',
            'is_enabled' => true,
        ]);
        $user = User::factory()->create([
            'name' => 'Tabbed User',
            'email' => 'tabbed@example.com',
            'country_code' => 'NL',
        ]);
        $user->categories()->sync([$category->id]);

        $this->actingAs($admin)
            ->get('/admin/users/'.$user->id.'/edit')
            ->assertOk()
            ->assertSee('tabmenu', false)
            ->assertSee('Profiel')
            ->assertSee('Categorie')
            ->assertSee('Members')
            ->assertSee('Land')
            ->assertSee('Netherlands (NL)')
            ->assertDontSee('Algemeen');

        $this->actingAs($admin)
            ->get('/admin/users/'.$user->id.'/edit/roles')
            ->assertOk()
            ->assertSee('Rollen');

        $this->actingAs($admin)
            ->get('/admin/users/'.$user->id.'/edit/image')
            ->assertOk()
            ->assertSee('Profielfoto');
    }

    public function test_admin_can_generate_and_disable_user_two_factor_key(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'name' => 'Two Factor User',
            'email' => 'two-factor@example.com',
        ]);

        $this->actingAs($admin)
            ->get('/admin/users/'.$user->id.'/edit/two-factor')
            ->assertOk()
            ->assertSee('Tweefactorauthenticatie')
            ->assertSee('Sleutel genereren');

        $this->actingAs($admin)
            ->post('/admin/users/'.$user->id.'/two-factor/generate')
            ->assertRedirect('/admin/users/'.$user->id.'/edit/two-factor');

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertTrue($user->hasTwoFactorEnabled());

        $this->actingAs($admin)
            ->get('/admin/users/'.$user->id.'/edit/two-factor')
            ->assertOk()
            ->assertSee('otpauth://totp', false)
            ->assertSee('<svg', false)
            ->assertSee('Sleutel opnieuw genereren');

        $this->actingAs($admin)
            ->delete('/admin/users/'.$user->id.'/two-factor')
            ->assertRedirect('/admin/users/'.$user->id.'/edit/two-factor');

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_admin_login_requires_valid_two_factor_code_when_user_has_two_factor_enabled(): void
    {
        $twoFactor = app(TwoFactorAuthenticator::class);
        $secret = $twoFactor->generateSecret();
        $admin = User::factory()->admin()->create([
            'email' => 'secure-admin@example.com',
            'password' => Hash::make('password'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])
            ->assertRedirect('/admin/login/two-factor');

        $this->assertGuest();

        $this->get('/admin')
            ->assertRedirect('/admin/login');

        $this->get('/admin/login/two-factor')
            ->assertOk()
            ->assertSee('Authenticator verification')
            ->assertSee('name="two_factor_code"', false);

        $this->post('/admin/login/two-factor', [
            'two_factor_code' => '000000',
        ])
            ->assertRedirect('/admin/login/two-factor')
            ->assertSessionHasErrors('two_factor_code');

        $this->post('/admin/login/two-factor', [
            'two_factor_code' => $twoFactor->code($secret),
        ])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_domain_can_make_backend_two_factor_mandatory(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'domain-admin@example.com',
            'password' => Hash::make('password'),
        ]);

        Domain::query()->create([
            'host' => 'localhost',
            'name' => 'Local',
            'default_locale' => 'nl',
            'settings' => [
                'security' => [
                    'backend_two_factor_required' => true,
                ],
            ],
            'is_active' => true,
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('two_factor_code');
    }

    public function test_admin_can_send_separate_frontend_and_backend_invitations(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->admin()->create([
            'email' => 'invitee@example.com',
        ]);

        Password::shouldReceive('sendResetLink')
            ->twice()
            ->with(['email' => 'invitee@example.com'])
            ->andReturn(PasswordBroker::RESET_LINK_SENT);

        $this->actingAs($admin)
            ->post('/admin/users/'.$user->id.'/invitation/frontend')
            ->assertRedirect();

        $this->actingAs($admin)
            ->post('/admin/users/'.$user->id.'/invitation/backend')
            ->assertRedirect();
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete('/admin/users/'.$admin->id)
            ->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'email' => $admin->email,
        ]);
    }

    public function test_admin_can_impersonate_another_admin_and_logout_back_to_original_account(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Original Admin',
        ]);
        $otherAdmin = User::factory()->admin()->create([
            'name' => 'Second Admin',
        ]);

        $this->actingAs($admin)
            ->post('/admin/users/'.$otherAdmin->id.'/impersonate')
            ->assertRedirect('/admin')
            ->assertSessionHas('admin_impersonator_id', $admin->id);

        $this->assertAuthenticatedAs($otherAdmin);

        $this->post('/admin/logout')
            ->assertRedirect('/admin')
            ->assertSessionMissing('admin_impersonator_id');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_legacy_cms_user_route_uses_the_rebuilt_module(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'legacy-admin',
        ]);

        $this->actingAs($admin)
            ->get('/cms/users/index.php')
            ->assertOk()
            ->assertSee('Gebruikers overzicht')
            ->assertSee('legacy-admin');
    }
}
