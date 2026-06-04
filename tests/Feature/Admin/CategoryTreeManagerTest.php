<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\CategoryTreeManager;
use App\Models\Cms\ContentCategory;
use App\Models\Cms\ContentItem;
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
            '/admin/banner/categorieen' => route('admin.banners.index'),
            '/admin/catalogus/categorieen' => route('admin.catalog.index'),
            '/admin/content/categorieen' => route('admin.content.index'),
            '/admin/download/categorieen' => route('admin.downloads.index'),
            '/admin/evenementen/categorieen' => route('admin.events.index'),
            '/admin/faq/categorieen' => route('admin.faq.index'),
            '/admin/form/categorieen' => route('admin.forms.index'),
            '/admin/vestigingen/categorieen' => route('admin.locations.index'),
            '/admin/users/categorieen' => route('admin.users.index'),
        ] as $url => $relatedOverviewUrl) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('category-related-items-button', false)
                ->assertSee('href="'.$relatedOverviewUrl.'"', false)
                ->assertSee('category-tree-manager', false)
                ->assertSee('Hoofdcategorie toevoegen');
        }
    }

    public function test_category_edit_pages_include_related_item_toolbar_button(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ContentCategory::query()->create([
            'name' => 'News category',
            'slug' => 'news-category',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->get("/admin/content/categorieen/{$category->id}/edit")
            ->assertOk()
            ->assertSee('category-related-items-button', false)
            ->assertSee('href="'.route('admin.content.index').'"', false);
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

            $component = Livewire::actingAs($admin)
                ->test(CategoryTreeManager::class, ['module' => $module])
                ->assertSee("Category {$module}")
                ->assertSee('category-tree-status active-item', false)
                ->call('selectCategory', $category->id)
                ->assertSet('selectedCategoryId', $category->id)
                ->assertDontSee('Totaal gekoppeld')
                ->assertDontSee('Slug')
                ->assertSee('Bekijk gekoppelde items');

            if ($module === 'forms') {
                $component
                    ->assertDontSee(url("/category-{$module}"))
                    ->assertDontSee('<dt>URL</dt>', false);
            } else {
                $component->assertDontSee(url("/category-{$module}"));
            }
        }
    }

    public function test_content_category_detail_places_linked_page_count_in_heading(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ContentCategory::query()->create([
            'name' => 'News category',
            'slug' => 'news-category',
            'status' => 'active',
            'sort_order' => 1,
        ]);
        $firstPage = ContentItem::query()->create([
            'title' => 'First linked page',
            'slug' => 'first-linked-page',
            'status' => 'published',
        ]);
        $secondPage = ContentItem::query()->create([
            'title' => 'Second linked page',
            'slug' => 'second-linked-page',
            'status' => 'published',
        ]);

        $category->contentItems()->attach([
            $firstPage->id => ['sort_order' => 1],
            $secondPage->id => ['sort_order' => 2],
        ]);

        Livewire::actingAs($admin)
            ->test(CategoryTreeManager::class, ['module' => 'content'])
            ->call('selectCategory', $category->id)
            ->assertSee("Pagina&#039;s", false)
            ->assertSee('(2)')
            ->assertSee('First linked page')
            ->assertSee('Second linked page')
            ->assertDontSee('Totaal gekoppeld')
            ->assertDontSee('Slug');
    }
}
