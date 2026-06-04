<?php

namespace Tests\Feature\Admin;

use App\Models\Cms\CatalogCategory;
use App\Models\Cms\CmsPermission;
use App\Models\Cms\CmsRole;
use App\Models\Cms\ContentCategory;
use App\Models\Cms\ContentItem;
use App\Models\Cms\NavigationMenu;
use App\Models\Cms\NavigationMenuItem;
use App\Models\User;
use App\Support\Admin\Roles\ModulePermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationBuilderModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_technical_name_is_only_visible_to_super_admin(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Primary navigation',
            'handle' => 'internal-primary',
            'is_active' => true,
        ]);
        $superAdmin = User::factory()->admin()->create();
        $roleAdmin = $this->roleBasedAdmin();
        $handleLabel = __('Handle');

        $this->actingAs($superAdmin)
            ->get(route('admin.navigation.index'))
            ->assertOk()
            ->assertSee($handleLabel)
            ->assertSee('internal-primary');

        $this->actingAs($roleAdmin)
            ->get(route('admin.navigation.index'))
            ->assertOk()
            ->assertDontSee($handleLabel)
            ->assertDontSee('internal-primary');

        $this->actingAs($superAdmin)
            ->get(route('admin.navigation.edit', $menu))
            ->assertOk()
            ->assertSee('name="handle"', false)
            ->assertSee('internal-primary');

        $this->actingAs($roleAdmin)
            ->get(route('admin.navigation.edit', $menu))
            ->assertOk()
            ->assertDontSee('name="handle"', false)
            ->assertDontSee('internal-primary');
    }

    public function test_role_based_admin_cannot_change_navigation_technical_name(): void
    {
        $roleAdmin = $this->roleBasedAdmin();
        $menu = NavigationMenu::query()->create([
            'name' => 'Primary navigation',
            'handle' => 'primary',
            'is_active' => true,
        ]);

        $this->actingAs($roleAdmin)
            ->put(route('admin.navigation.update', $menu), [
                'name' => 'Renamed navigation',
                'handle' => 'forged-handle',
                'is_active' => '1',
                'items_payload' => json_encode([]),
            ])
            ->assertRedirect(route('admin.navigation.edit', $menu));

        $menu->refresh();

        $this->assertSame('Renamed navigation', $menu->name);
        $this->assertSame('primary', $menu->handle);
    }

    public function test_role_based_admin_create_generates_navigation_technical_name_from_name(): void
    {
        $roleAdmin = $this->roleBasedAdmin();

        $this->actingAs($roleAdmin)
            ->post(route('admin.navigation.store'), [
                'name' => 'Footer navigation',
                'handle' => 'forged-handle',
                'is_active' => '1',
                'items_payload' => json_encode([]),
            ])
            ->assertRedirect();

        $menu = NavigationMenu::query()->firstOrFail();

        $this->assertSame('Footer navigation', $menu->name);
        $this->assertSame('footer-navigation', $menu->handle);
    }

    public function test_admin_can_search_link_targets_for_selector_modal(): void
    {
        $admin = User::factory()->admin()->create();

        $category = ContentCategory::query()->create([
            'name' => 'News',
            'slug' => 'news',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->getJson('/admin/navigation/link-options?type=content_category&q=new')
            ->assertOk()
            ->assertJsonFragment([
                'label' => 'News',
                'type' => 'content_category',
                'is_category' => true,
                'source_edit_url' => route('admin.content.categories.edit', ['id' => $category->id]),
            ]);
    }

    public function test_navigation_link_selector_filters_targets_by_navigation_language(): void
    {
        $admin = User::factory()->admin()->create();
        $dutchItem = ContentItem::query()->create([
            'title' => 'Nederlandse pagina',
            'slug' => 'nederlandse-pagina',
            'locale' => 'nl',
            'status' => 'published',
        ]);
        $englishItem = ContentItem::query()->create([
            'title' => 'English page',
            'slug' => 'english-page',
            'locale' => 'en',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->getJson('/admin/navigation/link-options?type=content_item&locale=nl')
            ->assertOk()
            ->assertJsonFragment([
                'label' => 'Nederlandse pagina',
                'locale' => 'nl',
                'flag_url' => asset('vendor/flag-icons/flags/4x3/nl.svg'),
                'source_edit_url' => route('admin.content.edit', ['id' => $dutchItem->id]),
            ])
            ->assertJsonMissing([
                'label' => 'English page',
                'source_edit_url' => route('admin.content.edit', ['id' => $englishItem->id]),
            ]);

        $this->actingAs($admin)
            ->getJson('/admin/navigation/link-options?type=content_item&locale=nl&all_languages=1')
            ->assertOk()
            ->assertJsonFragment(['label' => 'Nederlandse pagina'])
            ->assertJsonFragment(['label' => 'English page']);
    }

    public function test_navigation_builder_payload_includes_backend_source_edit_url(): void
    {
        $admin = User::factory()->admin()->create();
        $news = ContentCategory::query()->create([
            'name' => 'News',
            'slug' => 'news',
            'status' => 'active',
            'sort_order' => 1,
        ]);
        $menu = NavigationMenu::query()->create([
            'name' => 'Primary navigation',
            'handle' => 'primary',
            'is_active' => true,
        ]);

        NavigationMenuItem::query()->create([
            'navigation_menu_id' => $menu->id,
            'title' => 'News',
            'link_type' => 'content_category',
            'link_id' => $news->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.navigation.edit', $menu))
            ->assertOk();

        $this->assertStringContainsString('source_edit_url', $response->getContent());
        $this->assertStringContainsString(
            str_replace('/', '\\/', route('admin.content.categories.edit', ['id' => $news->id])),
            $response->getContent(),
        );
    }

    public function test_admin_can_save_nested_navigation_items(): void
    {
        $admin = User::factory()->admin()->create();
        $news = ContentCategory::query()->create([
            'name' => 'News',
            'slug' => 'news',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->post('/admin/navigation', [
                'name' => 'Primary navigation',
                'handle' => 'primary',
                'is_active' => '1',
                'items_payload' => json_encode([
                    [
                        'title' => 'News',
                        'link_type' => 'content_category',
                        'link_id' => $news->id,
                        'is_active' => true,
                        'expand_children' => true,
                        'children' => [
                            [
                                'title' => 'Partner portal',
                                'link_type' => 'custom',
                                'custom_url' => 'https://partner.example.test',
                                'is_active' => true,
                                'children' => [],
                            ],
                        ],
                    ],
                ]),
            ])
            ->assertRedirect();

        $menu = NavigationMenu::query()->firstOrFail();
        $parent = NavigationMenuItem::query()->where('title', 'News')->firstOrFail();
        $child = NavigationMenuItem::query()->where('title', 'Partner portal')->firstOrFail();

        $this->assertSame('primary', $menu->handle);
        $this->assertTrue($parent->expand_children);
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertTrue($child->opens_new_tab);
    }

    public function test_navigation_overview_and_edit_screen_show_language_choices(): void
    {
        $admin = User::factory()->admin()->create();
        $menu = NavigationMenu::query()->create([
            'name' => 'Nederlandse navigatie',
            'handle' => 'primary',
            'locale' => 'nl',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.navigation.index'))
            ->assertOk()
            ->assertSee('Taal')
            ->assertSee('vendor/flag-icons/flags/4x3/nl.svg', false)
            ->assertSee('NL');

        $this->actingAs($admin)
            ->get(route('admin.navigation.edit', $menu))
            ->assertOk()
            ->assertSee('name="locale"', false)
            ->assertSee('data-navigation-locale="nl"', false)
            ->assertDontSee('name="locale" type="text"', false);
    }

    public function test_frontend_navigation_renders_builder_items_and_expanded_category_children(): void
    {
        $products = CatalogCategory::query()->create([
            'name' => 'Products',
            'slug' => 'products',
            'status' => 'active',
            'sort_order' => 1,
        ]);
        CatalogCategory::query()->create([
            'parent_id' => $products->id,
            'name' => 'Chairs',
            'slug' => 'chairs',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $menu = NavigationMenu::query()->create([
            'name' => 'Primary navigation',
            'handle' => 'primary',
            'is_active' => true,
        ]);

        NavigationMenuItem::query()->create([
            'navigation_menu_id' => $menu->id,
            'title' => 'Products',
            'link_type' => 'catalog_category',
            'link_id' => $products->id,
            'expand_children' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NavigationMenuItem::query()->create([
            'navigation_menu_id' => $menu->id,
            'title' => 'External',
            'link_type' => 'custom',
            'custom_url' => 'https://example.com',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Products')
            ->assertSee('Chairs')
            ->assertSee('href="http://localhost/products"', false)
            ->assertSee('href="http://localhost/products/chairs"', false)
            ->assertSee('href="https://example.com"', false)
            ->assertSee('target="_blank"', false);
    }

    private function roleBasedAdmin(): User
    {
        app(ModulePermissionRegistry::class)->syncPermissions();

        $role = CmsRole::query()->create([
            'name' => 'Navigation editor',
            'slug' => 'navigation-editor',
            'status' => 'active',
        ]);

        $role->syncPermissionIds(
            CmsPermission::query()
                ->where('permission_key', 'admin.access')
                ->pluck('id'),
        );

        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'role_id' => $role->id,
        ]);

        $user->roles()->sync([$role->id => ['is_primary' => true]]);

        return $user;
    }
}
