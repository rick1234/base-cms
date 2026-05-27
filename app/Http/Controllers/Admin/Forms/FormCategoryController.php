<?php

namespace App\Http\Controllers\Admin\Forms;

use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Forms\FormCategoryRequest;
use App\Models\Cms\FormCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FormCategoryController extends Controller
{
    use UsesEditViewForCreate;

    public function index(): View
    {
        return view('admin.forms.categories.index', [
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(FormCategoryRequest $request): RedirectResponse
    {
        $category = $this->saveCategory($request);

        flash(__('Form category created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $category->id]);
    }

    public function edit(Request $request): View
    {
        $category = $this->categoryFromRequest($request);

        return view('admin.forms.categories.edit', [
            'category' => $category ?? new FormCategory(['status' => 'active']),
            'categories' => FormCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit form category'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $category ? route($this->routeName('destroy'), $category) : null,
        ]);
    }

    public function save(FormCategoryRequest $request): RedirectResponse
    {
        $category = $this->saveCategory($request, $request->category());

        flash(__('Form category saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $category->id]);
    }

    public function destroy(FormCategory $formCategory): RedirectResponse
    {
        $formCategory->delete();

        flash(__('Form category deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    private function saveCategory(FormCategoryRequest $request, ?FormCategory $category = null): FormCategory
    {
        $category ??= new FormCategory(['created_by' => $request->user()?->id]);

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

    private function categoryFromRequest(Request $request): ?FormCategory
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? FormCategory::query()->findOrFail($id) : null;
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
        return (request()->routeIs('cms.*') ? 'cms.forms.categories.' : 'admin.forms.categories.').$name;
    }
}
