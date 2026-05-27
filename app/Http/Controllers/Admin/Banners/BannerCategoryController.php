<?php

namespace App\Http\Controllers\Admin\Banners;

use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Banners\BannerCategoryRequest;
use App\Models\Cms\BannerCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BannerCategoryController extends Controller
{
    use UsesEditViewForCreate;

    public function index(): View
    {
        return view('admin.banners.categories.index', [
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(BannerCategoryRequest $request): RedirectResponse
    {
        $category = $this->saveCategory($request);

        flash(__('Banner category created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $category->id]);
    }

    public function edit(Request $request): View
    {
        $category = $this->categoryFromRequest($request);

        return view('admin.banners.categories.edit', [
            'category' => $category ?? new BannerCategory([
                'parent_id' => $request->integer('parent') ?: null,
                'status' => 'active',
            ]),
            'categories' => BannerCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit banner category'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $category ? route($this->routeName('destroy'), $category) : null,
        ]);
    }

    public function save(BannerCategoryRequest $request): RedirectResponse
    {
        $category = $this->saveCategory($request, $request->category());

        flash(__('Banner category saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $category->id]);
    }

    public function destroy(BannerCategory $bannerCategory): RedirectResponse
    {
        $bannerCategory->delete();

        flash(__('Banner category deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    private function saveCategory(BannerCategoryRequest $request, ?BannerCategory $category = null): BannerCategory
    {
        $category ??= new BannerCategory(['created_by' => $request->user()?->id]);

        $attributes = Arr::only($request->validated(), [
            'parent_id',
            'name',
            'slug',
            'description',
            'status',
            'is_hidden_from_navigation',
            'sort_order',
        ]);

        $attributes['slug'] = filled($attributes['slug'] ?? null)
            ? Str::slug((string) $attributes['slug'])
            : Str::slug((string) $attributes['name']);
        $attributes['updated_by'] = $request->user()?->id;

        $category->fill($attributes)->save();

        return $category->refresh();
    }

    private function categoryFromRequest(Request $request): ?BannerCategory
    {
        $id = (int) ($request->route('id') ?: $request->integer('id') ?: $request->integer('node_id'));

        return $id > 0 ? BannerCategory::query()->findOrFail($id) : null;
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
        return (request()->routeIs('cms.*') ? 'cms.banners.categories.' : 'admin.banners.categories.').$name;
    }
}
