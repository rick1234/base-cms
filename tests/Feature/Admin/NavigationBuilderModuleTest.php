<?php

namespace Tests\Feature\Admin;

use App\Models\Cms\CatalogCategory;
use App\Models\Cms\ContentCategory;
use App\Models\Cms\NavigationMenu;
use App\Models\Cms\NavigationMenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationBuilderModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_link_targets_for_selector_modal(): void
    {
        $admin = User::factory()->admin()->create();

        ContentCategory::query()->create([
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
            ]);
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
}
