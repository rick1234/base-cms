<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\CategoryTreeManager;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryTreeManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_index_pages_mount_reusable_livewire_manager(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([
            '/admin/banner/categorieen',
            '/admin/catalogus/categorieen',
            '/admin/content/categorieen',
            '/admin/download/categorieen',
            '/admin/evenementen/categorieen',
            '/admin/faq/categorieen',
            '/admin/form/categorieen',
            '/admin/vestigingen/categorieen',
            '/admin/users/categorieen',
        ] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('category-tree-manager', false)
                ->assertSee('Hoofdcategorie toevoegen');
        }
    }

    public function test_reusable_category_tree_manager_renders_each_category_module(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (array_keys(config('cms_categories')) as $module) {
            /** @var class-string<Model> $model */
            $model = config("cms_categories.{$module}.model");
            $category = $model::query()->create([
                'name' => "Category {$module}",
                'slug' => "category-{$module}",
                'status' => 'active',
                'sort_order' => 1,
            ]);

            Livewire::actingAs($admin)
                ->test(CategoryTreeManager::class, ['module' => $module])
                ->assertSee("Category {$module}")
                ->assertSee('category-tree-status active-item', false)
                ->call('selectCategory', $category->id)
                ->assertSet('selectedCategoryId', $category->id)
                ->assertSee(url("/category-{$module}"))
                ->assertSee('Totaal gekoppeld')
                ->assertSee('Bekijk gekoppelde items');
        }
    }
}
