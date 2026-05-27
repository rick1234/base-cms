<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Cms\UserCategory;
use App\Models\Cms\CmsRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
