<?php

namespace App\Http\Controllers\Admin\Downloads;

use App\Actions\Admin\Downloads\UpsertDownloadCategory;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Downloads\DownloadCategoryRequest;
use App\Models\Cms\DownloadCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DownloadCategoryController extends Controller
{
    use UsesEditViewForCreate;

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('categoryId') && $request->filled('sortDirection')) {
            $this->moveCategory($request->integer('categoryId'), $request->string('sortDirection')->toString());

            return redirect()->route($this->routeName('index'), ['categoryId' => $request->integer('categoryId')]);
        }

        return view('admin.downloads.categories.index', [
            'routeNames' => $this->routeNames(),
            'pageName' => __('Download category overview'),
        ]);
    }

    public function store(DownloadCategoryRequest $request, UpsertDownloadCategory $upsert): RedirectResponse
    {
        $category = $upsert->handle($request->validated(), $request->user());

        flash(__('Category created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $category->id]);
    }

    public function edit(Request $request): View
    {
        $category = $this->categoryFromRequest($request);
        $categories = $this->categories();

        return view('admin.downloads.categories.edit', [
            'category' => $category ?? new DownloadCategory([
                'parent_id' => $request->integer('parent') ?: null,
                'status' => 'active',
            ]),
            'categories' => $categories,
            'categoriesByParent' => $categories->groupBy(fn (DownloadCategory $category): int => $category->parent_id ?: 0),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit download category'),
            'backUrl' => route($this->routeName('index'), array_filter(['categoryId' => $category?->id])),
        ]);
    }

    public function save(DownloadCategoryRequest $request, UpsertDownloadCategory $upsert): RedirectResponse
    {
        $category = $upsert->handle($request->validated(), $request->user(), $request->category());

        flash(__('Category saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $category->id]);
    }

    public function destroy(DownloadCategory $downloadCategory): RedirectResponse
    {
        $downloadCategory->delete();

        flash(__('Category deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    private function categoryFromRequest(Request $request): ?DownloadCategory
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? DownloadCategory::query()->findOrFail($id) : null;
    }

    /**
     * @return Collection<int, DownloadCategory>
     */
    private function categories(): Collection
    {
        return DownloadCategory::query()
            ->withCount('downloads')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function moveCategory(int $categoryId, string $direction): void
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        $category = DownloadCategory::query()->find($categoryId);

        if (! $category) {
            return;
        }

        $siblings = DownloadCategory::query()
            ->where('parent_id', $category->parent_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $currentIndex = $siblings->search(fn (DownloadCategory $sibling): bool => $sibling->id === $category->id);
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
        return (request()->routeIs('cms.*') ? 'cms.downloads.categories.' : 'admin.downloads.categories.').$name;
    }
}
