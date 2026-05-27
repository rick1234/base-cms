<?php

namespace App\Http\Controllers\Admin\Content;

use App\Actions\Admin\Content\UpsertContentCategory;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\ContentCategoryRequest;
use App\Http\Requests\Admin\Content\ContentMediaRequest;
use App\Http\Requests\Admin\Content\ContentSliderRequest;
use App\Models\Cms\ContentCategory;
use App\Models\Cms\ContentCategoryImage;
use App\Models\Cms\SliderCategory;
use App\Support\Admin\Content\ContentMediaManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ContentCategoryController extends Controller
{
    use UsesEditViewForCreate;

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('categoryId') && $request->filled('sortDirection')) {
            $this->moveCategory($request->integer('categoryId'), $request->string('sortDirection')->toString());

            return redirect()->route($this->routeName('index'), ['categoryId' => $request->integer('categoryId')]);
        }

        return view('admin.content.categories.index', [
            'routeNames' => $this->routeNames(),
            'pageName' => __('Content Categories'),
        ]);
    }

    public function store(ContentCategoryRequest $request, UpsertContentCategory $upsert): RedirectResponse
    {
        $category = $upsert->handle($request->validated(), $request->user(), null, $request->file('images', []));

        flash(__('Category created.'))->success();

        return redirect()
            ->route($this->routeName('edit'), ['id' => $category->id]);
    }

    public function edit(Request $request): View
    {
        $category = $this->categoryFromRequest($request);
        $category?->load('images');
        $categories = $this->categories();

        return view('admin.content.categories.edit', [
            'category' => $category ?? new ContentCategory([
                'parent_id' => $request->integer('parent') ?: null,
                'status' => 'active',
            ]),
            'categories' => $categories,
            'categoriesByParent' => $categories->groupBy(fn (ContentCategory $category): int => $category->parent_id ?: 0),
            'sliderCategories' => SliderCategory::query()->orderBy('name')->get(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit content category'),
            'backUrl' => route($this->routeName('index'), array_filter(['categoryId' => $category?->id])),
        ]);
    }

    public function save(ContentCategoryRequest $request, UpsertContentCategory $upsert): RedirectResponse
    {
        $category = $upsert->handle(
            $request->validated(),
            $request->user(),
            $request->contentCategory(),
            $request->file('images', []),
        );

        flash(__('Category saved.'))->success();

        return redirect()
            ->route($this->routeName('edit'), ['id' => $category->id]);
    }

    public function destroy(ContentCategory $contentCategory): RedirectResponse
    {
        $contentCategory->delete();

        flash(__('Category deleted.'))->success();

        return redirect()
            ->route($this->routeName('index'));
    }

    public function slider(Request $request): View
    {
        $category = $this->categoryFromRequest($request);

        return view('admin.content.categories.slider', [
            'category' => $category,
            'sliderCategories' => SliderCategory::query()->orderBy('name')->get(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Category slider'),
            'backUrl' => route($this->routeName('index'), array_filter(['categoryId' => $category?->id])),
        ]);
    }

    public function saveSlider(ContentSliderRequest $request, UpsertContentCategory $upsert): RedirectResponse
    {
        $category = $request->contentCategory();

        abort_unless($category, 404);

        $data = [
            ...$category->only([
                'parent_id',
                'name',
                'slug',
                'custom_url',
                'description',
                'meta_description',
                'image_path',
                'status',
                'is_hidden_from_navigation',
                'sort_order',
            ]),
            'slider_category_id' => $request->validated('slider_category_id'),
        ];

        $upsert->handle($data, $request->user(), $category);

        flash(__('Slider settings saved.'))->success();

        return redirect()
            ->route($this->routeName('slider'), ['id' => $category->id]);
    }

    public function uploadImage(ContentMediaRequest $request, ContentMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $category = ContentCategory::query()->findOrFail($request->integer('id'));
        $file = $request->file('file') ?: $request->file('image');

        abort_unless($file, 422);

        $image = $mediaManager->storeCategoryImage($category, $file, $request->string('caption')->toString() ?: null, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Image uploaded.'))->success();

            return back();
        }

        return response()->json([
            'jsonrpc' => '2.0',
            'status' => 'success',
            'result' => $image->id,
            'id' => $image->id,
        ]);
    }

    public function updateImageName(ContentMediaRequest $request): JsonResponse|RedirectResponse
    {
        $image = ContentCategoryImage::query()->findOrFail($request->integer('uploadId') ?: $request->integer('id'));
        $image->fill([
            'caption' => $request->input('uploadName', $request->input('caption')),
            'updated_by' => $request->user()?->id,
        ])->save();

        if (! $request->expectsJson()) {
            flash(__('Name updated.'))->success();

            return back();
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Name updated.'),
        ]);
    }

    public function updateImageSort(ContentMediaRequest $request, ContentMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $ids = collect(explode(',', (string) $request->input('sort_index')))
            ->map(fn (string $id): int => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $mediaManager->updateSortOrder(ContentCategoryImage::class, $ids, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Sort order updated.'))->success();

            return back();
        }

        return response()->json(['status' => 'success']);
    }

    public function deleteImage(ContentMediaRequest $request, ContentMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $image = ContentCategoryImage::query()->findOrFail($request->integer('id'));
        $mediaManager->deleteMedia($image, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Image deleted.'))->success();

            return back();
        }

        return response()->json(['status' => 'success']);
    }

    private function categoryFromRequest(Request $request): ?ContentCategory
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? ContentCategory::query()->findOrFail($id) : null;
    }

    /**
     * @return Collection<int, ContentCategory>
     */
    private function categories(): Collection
    {
        return ContentCategory::query()
            ->withCount('contentItems')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function moveCategory(int $categoryId, string $direction): void
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        $category = ContentCategory::query()->find($categoryId);

        if (! $category) {
            return;
        }

        $siblings = ContentCategory::query()
            ->where('parent_id', $category->parent_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $currentIndex = $siblings->search(fn (ContentCategory $sibling): bool => $sibling->id === $category->id);
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
            'slider' => $this->routeName('slider'),
            'slider.save' => $this->routeName('slider.save'),
            'image.delete' => $this->routeName('image.delete'),
            'image.update-name' => $this->routeName('image.update-name'),
            'image.update-sort' => $this->routeName('image.update-sort'),
            'image.upload' => $this->routeName('image.upload'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.content.categories.' : 'admin.content.categories.').$name;
    }
}
