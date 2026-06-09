<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseModuleConventionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_base_module_conventions(): void
    {
        $this->get('/admin/base-module-conventions')
            ->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_base_module_conventions_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/base-module-conventions')
            ->assertOk()
            ->assertSee(__('Base module conventions'))
            ->assertSee(__('Base module conventions overview'))
            ->assertSee('content-overview-container', false)
            ->assertSee(__('Reference content item'))
            ->assertSee('/admin/base-module-conventions/23/edit', false)
            ->assertSee('/admin/base-module-conventions/categories', false);
    }

    public function test_admin_can_click_into_base_module_conventions_edit_tabs(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/base-module-conventions/23/edit')
            ->assertOk()
            ->assertSee(__('Bewerk: :title', ['title' => __('Reference content item')]))
            ->assertSee('tabmenu', false)
            ->assertSee('admin.index.content')
            ->assertSee('admin.edit.content')
            ->assertSee('/admin/base-module-conventions/23/images', false)
            ->assertSee('/admin/base-module-conventions/23/edit/form', false)
            ->assertSee('/admin/base-module-conventions/23/edit/seo', false)
            ->assertSee(__('Blokken toevoegen'))
            ->assertSee(__('Title block'))
            ->assertDontSee('/admin/base-module-conventions/23/edit/blocks', false)
            ->assertDontSee('/admin/base-module-conventions/23/edit/submissions', false);

        $this->actingAs($admin)
            ->get('/admin/base-module-conventions/23/images')
            ->assertOk()
            ->assertSee(__('Fotoalbum: :title', ['title' => __('Reference content item')]))
            ->assertSee(__('Hero image'))
            ->assertSee('/admin/base-module-conventions/23/edit', false);

        $this->actingAs($admin)
            ->get('/admin/base-module-conventions/23/edit/form')
            ->assertOk()
            ->assertSee(__('Formulier'))
            ->assertSee(__('Kies een formulier'));

        $this->actingAs($admin)
            ->get('/admin/base-module-conventions/23/edit/seo')
            ->assertOk()
            ->assertSee(__('SEO settings'))
            ->assertSee(__('Meta omschrijving'));
    }

    public function test_admin_can_open_base_module_convention_categories(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/base-module-conventions/categories')
            ->assertOk()
            ->assertSee(__('Base convention category overview'))
            ->assertSee(__('Landing pages'));
    }

    public function test_base_module_conventions_are_visible_in_admin_navigation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('/admin/base-module-conventions', false)
            ->assertSee(__('Base module conventions'));
    }

    public function test_legacy_cms_path_can_view_base_module_conventions_reference_screen(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/cms/base-module-conventions')
            ->assertOk()
            ->assertSee(__('Base module conventions'))
            ->assertSee('/cms/base-module-conventions/23/edit', false);
    }
}
