<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Actions\Admin\Catalog\UpsertCatalogCategory;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\CatalogCategoryRequest;
use App\Models\Cms\CatalogCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CatalogCategoryController extends Controller
{
    use UsesEditViewForCreate;

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('categoryId') && $request->filled('sortDirection')) {
            $this->moveCategory($request->integer('categoryId'), $request->string('sortDirection')->toString());

            return redirect()->route($this->routeName('index'), ['categoryId' => $request->integer('categoryId')]);
        }

        return view('admin.catalog.categories.index', [
            'routeNames' => $this->routeNames(),
            'pageName' => __('Catalog Categories'),
        ]);
    }

    public function store(CatalogCategoryRequest $request, UpsertCatalogCategory $upsert): RedirectResponse
    {
        $category = $upsert->handle($request->validated(), $request->user());

        flash(__('Category created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $category->id]);
    }

    public function edit(Request $request): View
    {
        $category = $this->categoryFromRequest($request);
        $categories = $this->categories();

        return view('admin.catalog.categories.edit', [
            'category' => $category ?? new CatalogCategory([
                'parent_id' => $request->integer('parent') ?: null,
                'status' => 'active',
            ]),
            'categories' => $categories,
            'categoriesByParent' => $categories->groupBy(fn (CatalogCategory $category): int => $category->parent_id ?: 0),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit catalog category'),
            'backUrl' => route($this->routeName('index'), array_filter(['categoryId' => $category?->id])),
        ]);
    }

    public function save(CatalogCategoryRequest $request, UpsertCatalogCategory $upsert): RedirectResponse
    {
        $category = $upsert->handle($request->validated(), $request->user(), $request->category());

        flash(__('Category saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $category->id]);
    }

    public function destroy(CatalogCategory $catalogCategory): RedirectResponse
    {
        $catalogCategory->delete();

        flash(__('Category deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    private function categoryFromRequest(Request $request): ?CatalogCategory
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? CatalogCategory::query()->findOrFail($id) : null;
    }

    /**
     * @return Collection<int, CatalogCategory>
     */
    private function categories(): Collection
    {
        return CatalogCategory::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function moveCategory(int $categoryId, string $direction): void
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        $category = CatalogCategory::query()->find($categoryId);

        if (! $category) {
            return;
        }

        $siblings = CatalogCategory::query()
            ->where('parent_id', $category->parent_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $currentIndex = $siblings->search(fn (CatalogCategory $sibling): bool => $sibling->id === $category->id);
        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($currentIndex === false || ! isset($siblings[$targetIndex])) {
            return;
        }

        $target = $siblings[$targetIndex];
        $currentSort = $category->sort_order;

        $category->forceFill(['sort_order' => $target->sort_order])->save();
        $target->forceFill(['sort_order' => $currentSort])->save();
    }

    /**
     * @return array<string, string>
     */
    private function routeNames(): array
    {
        return [
            'index' => $this->routeName('index'),
            'store' => $this->routeName('store'),
            'create' => request()->routeIs('cms.*') ? $this->routeName('edit') : $this->routeName('create'),
            'edit' => $this->routeName('edit'),
            'save' => $this->routeName('save'),
            'destroy' => $this->routeName('destroy'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.catalog.categories.' : 'admin.catalog.categories.').$name;
    }
}
