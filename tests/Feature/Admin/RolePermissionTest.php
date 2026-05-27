<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Cms\CmsPermission;
use App\Models\Cms\CmsRole;
use App\Support\Admin\Roles\ModulePermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_editor_exposes_module_field_and_block_permissions(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/roles/edit')
            ->assertOk()
            ->assertSee('Backend toegang')
            ->assertSee('Contentblokken')
            ->assertSee('Bijlagen')
            ->assertSee('Agenda')
            ->assertSee('Velden')
            ->assertSee('Blokken');

        $this->assertDatabaseHas('permissions', [
            'permission_key' => 'admin.block.content_items.attachments',
            'module_key' => 'content_items',
            'permission_type' => 'block',
        ]);
        $this->assertDatabaseHas('permissions', [
            'permission_key' => 'admin.block.events.agenda',
            'module_key' => 'events',
            'permission_type' => 'block',
        ]);
    }

    public function test_admin_can_create_role_with_module_field_and_block_permissions(): void
    {
        $admin = User::factory()->admin()->create();
        app(ModulePermissionRegistry::class)->syncPermissions();

        $permissionIds = CmsPermission::query()
            ->whereIn('permission_key', [
                'admin.access',
                'admin.module.content_items.view',
                'admin.module.content_items.update',
                'admin.field.content_items.meta_description',
                'admin.block.content_items.attachments',
                'admin.block.events.agenda',
            ])
            ->pluck('id')
            ->all();

        $this->actingAs($admin)
            ->post('/admin/roles/edit', [
                'name' => 'Content manager',
                'slug' => 'content-manager',
                'description' => 'Can manage content fields and blocks.',
                'status' => 'active',
                'permissions' => $permissionIds,
            ])
            ->assertRedirect('/admin/roles/1/edit');

        $role = CmsRole::query()->where('slug', 'content-manager')->firstOrFail();

        $this->assertTrue($role->hasPermission('admin.access'));
        $this->assertTrue($role->hasPermission('admin.field.content_items.meta_description'));
        $this->assertFalse($role->hasPermission('admin.field.content_items.body'));
        $this->assertTrue($role->hasPermission('admin.block.content_items.attachments'));
        $this->assertTrue($role->hasPermission('admin.block.events.agenda'));
    }

    public function test_role_can_grant_admin_access_to_non_admin_user(): void
    {
        app(ModulePermissionRegistry::class)->syncPermissions();
        $role = CmsRole::query()->create([
            'name' => 'Dashboard user',
            'slug' => 'dashboard-user',
            'status' => 'active',
        ]);
        $role->syncPermissionIds(
            CmsPermission::query()
                ->whereIn('permission_key', ['admin.access', 'admin.module.content_items.view'])
                ->pluck('id')
        );

        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'role_id' => $role->id,
        ]);
        $user->roles()->sync([$role->id => ['is_primary' => true]]);

        $this->assertTrue($user->fresh()->hasAdminPermission('admin.access'));

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard');
    }

    public function test_user_edit_can_assign_multiple_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $firstRole = CmsRole::query()->create(['name' => 'Editor', 'slug' => 'editor', 'status' => 'active']);
        $secondRole = CmsRole::query()->create(['name' => 'Publisher', 'slug' => 'publisher', 'status' => 'active']);

        $this->actingAs($admin)
            ->post('/admin/users/edit', [
                'name' => 'Role user',
                'email' => 'role-user@example.com',
                'username' => 'role-user',
                'password' => 'secret-password',
                'is_active' => '1',
                'is_admin' => '0',
                'roles' => [$firstRole->id, $secondRole->id],
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'role-user@example.com')->firstOrFail();

        $this->assertSame($firstRole->id, $user->role_id);
        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => $firstRole->id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => $secondRole->id,
            'is_primary' => false,
        ]);
    }

    public function test_legacy_role_route_uses_rebuilt_role_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/cms/roles/index.php')
            ->assertOk()
            ->assertSee('Roles and Permissions');
    }

    public function test_old_rbac_routes_are_not_part_of_the_admin_surface(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/rbac/edit')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get('/cms/rbac/index.php')
            ->assertNotFound();
    }
}
