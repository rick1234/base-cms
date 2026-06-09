<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Actions\Admin\Catalog\DuplicateCatalogProduct;
use App\Actions\Admin\Catalog\UpsertCatalogProduct;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\CatalogMediaRequest;
use App\Http\Requests\Admin\Catalog\CatalogProductRequest;
use App\Http\Requests\Admin\Catalog\CatalogProductUtilityRequest;
use App\Models\Cms\CatalogBrand;
use App\Models\Cms\CatalogCategory;
use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogProductImage;
use App\Models\Cms\CatalogProductOption;
use App\Models\Cms\CatalogProductOptionValue;
use App\Models\Cms\CatalogProductTranslation;
use App\Models\Cms\CatalogProductVideo;
use App\Support\Admin\Catalog\CatalogMediaManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CatalogProductController extends Controller
{
    use UsesEditViewForCreate;

    public function index(Request $request): View|RedirectResponse
    {
        if ($this->shouldMoveProduct($request)) {
            $this->moveProductWithinCategory($request->integer('categoryId'), $request->integer('move'), $request->string('direction')->toString());

            return redirect()->route($this->routeName('index'), $request->except(['move', 'direction']));
        }

        $categories = $this->categories();
        $categoryId = $request->integer('categoryId');
        $query = CatalogProduct::query()
            ->with(['brand', 'categories'])
            ->withCount(['attachments', 'images', 'options', 'translations', 'videos']);

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('sku') || $request->filled('artikelnummer')) {
            $query->where('sku', 'like', '%'.$request->input('sku', $request->input('artikelnummer')).'%');
        }

        if ($request->filled('name') || $request->filled('naam')) {
            $query->where('name', 'like', '%'.$request->input('name', $request->input('naam')).'%');
        }

        if ($request->filled('status') && $this->normalizeStatus($request->input('status')) !== null) {
            $query->where('status', $this->normalizeStatus($request->input('status')));
        }

        if ($categoryId > 0) {
            $categoryIds = [$categoryId];

            if ($request->boolean('showChild')) {
                $categoryIds = [...$categoryIds, ...$this->descendantIds($categories, $categoryId)];
            }

            $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('catalog_categories.id', $categoryIds));
        }

        $products = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.catalog.products.index', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategoryId' => $categoryId,
            'showChild' => $request->boolean('showChild'),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(CatalogProductRequest $request, UpsertCatalogProduct $upsert): RedirectResponse
    {
        $product = $upsert->handle(
            $request->validated(),
            $request->user(),
            null,
            $this->uploadedFiles($request->file('attachment_files') ?: $request->file('attachment')),
        );

        flash(__('Product created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $product->id]);
    }

    public function edit(Request $request): View
    {
        $product = $this->productFromRequest($request);
        $product?->load(['attachments', 'brand', 'categories', 'images', 'options', 'combinationSets.products', 'translations', 'videos']);

        return view('admin.catalog.products.edit', [
            'product' => $product ?? new CatalogProduct([
                'price' => 0,
                'status' => 'draft',
                'active_from' => now(),
            ]),
            'brands' => CatalogBrand::query()->orderBy('name')->get(),
            'categories' => $this->categories(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit catalog product'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $product ? route($this->routeName('destroy'), $product) : null,
        ]);
    }

    public function save(CatalogProductRequest $request, UpsertCatalogProduct $upsert): RedirectResponse
    {
        $product = $upsert->handle(
            $request->validated(),
            $request->user(),
            $request->product(),
            $this->uploadedFiles($request->file('attachment_files') ?: $request->file('attachment')),
        );

        flash(__('Product saved.'))->success();

        return $this->redirectToProductEdit($product, $request);
    }

    public function update(CatalogProduct $catalogProduct, CatalogProductRequest $request, UpsertCatalogProduct $upsert): RedirectResponse
    {
        $product = $upsert->handle(
            $request->validated(),
            $request->user(),
            $catalogProduct,
            $this->uploadedFiles($request->file('attachment_files') ?: $request->file('attachment')),
        );

        flash(__('Product saved.'))->success();

        return $this->redirectToProductEdit($product, $request);
    }

    public function destroy(CatalogProduct $catalogProduct): RedirectResponse
    {
        $catalogProduct->delete();

        flash(__('Product deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    public function duplicate(Request $request, DuplicateCatalogProduct $duplicate): JsonResponse|RedirectResponse
    {
        $id = $request->integer('itemId') ?: $request->integer('item_id') ?: $request->integer('id');
        $product = CatalogProduct::query()
            ->with(['attachments', 'categories', 'images', 'options', 'combinationSets', 'translations', 'videos'])
            ->findOrFail($id);

        $copy = $duplicate->handle($product, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Product duplicated.'))->success();

            return redirect()->route($this->routeName('edit'), ['id' => $copy->id]);
        }

        return response()->json([
            'status' => 'success',
            'id' => $copy->id,
            'edit_url' => route($this->routeName('edit'), ['id' => $copy->id]),
        ]);
    }

    public function images(Request $request): View
    {
        $product = $this->productForUtility($request);

        if (! $product) {
            return $this->utilityPlaceholder(__('Product images'));
        }

        $product->load('images');

        return view('admin.catalog.products.images', [
            'product' => $product,
            'routeNames' => $this->routeNames(),
            'pageName' => __('Product images'),
            'backUrl' => route($this->routeName('edit'), ['id' => $product->id]),
        ]);
    }

    public function uploadImage(CatalogMediaRequest $request, CatalogMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $product = CatalogProduct::query()->findOrFail((int) ($request->route('id') ?: $request->integer('id') ?: $request->integer('catalog_product_id')));
        $files = collect([$request->file('file'), $request->file('image')])
            ->filter()
            ->merge($request->file('images', []))
            ->values();

        abort_unless($files->isNotEmpty(), 422);

        $images = $files->map(function ($file) use ($request, $product, $mediaManager): CatalogProductImage {
            $caption = $request->string('caption')->toString() ?: $this->defaultCaptionForUpload($file);

            return $mediaManager->storeImage($product, $file, $caption, $request->user(), [
                'alt_text' => $request->string('alt_text')->toString() ?: $caption,
                'title_text' => $request->string('title_text')->toString() ?: $caption,
                'description' => $request->string('description')->toString() ?: null,
                'credit' => $request->string('credit')->toString() ?: null,
                'is_decorative' => $request->boolean('is_decorative'),
            ]);
        });

        if (! $request->expectsJson()) {
            flash(trans_choice('{1} Image uploaded.|[2,*] Images uploaded.', $images->count()))->success();

            return back();
        }

        $image = $images->first();

        return response()->json([
            'jsonrpc' => '2.0',
            'status' => 'success',
            'result' => $image->id,
            'id' => $image->id,
            'count' => $images->count(),
        ]);
    }

    public function updateImageName(CatalogMediaRequest $request): JsonResponse|RedirectResponse
    {
        $image = CatalogProductImage::query()->findOrFail($request->integer('uploadId') ?: $request->integer('id'));
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

    public function updateImageSort(CatalogMediaRequest $request, CatalogMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $ids = collect(explode(',', (string) $request->input('sort_index')))
            ->map(fn (string $id): int => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $mediaManager->updateSortOrder(CatalogProductImage::class, $ids, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Sort order updated.'))->success();

            return back();
        }

        return response()->json(['status' => 'success']);
    }

    public function deleteImage(CatalogMediaRequest $request, CatalogMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $image = CatalogProductImage::query()->findOrFail($request->integer('id'));
        $mediaManager->deleteMedia($image, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Image deleted.'))->success();

            return back();
        }

        return response()->json(['status' => 'success']);
    }

    public function options(Request $request): View
    {
        $product = $this->productForUtility($request);

        if (! $product) {
            return $this->utilityPlaceholder(__('Product options'));
        }

        $product->load('options.values');

        return view('admin.catalog.products.options', [
            'product' => $product,
            'routeNames' => $this->routeNames(),
            'pageName' => __('Product options'),
            'backUrl' => route($this->routeName('edit'), ['id' => $product->id]),
        ]);
    }

    public function saveOptions(CatalogProductUtilityRequest $request): RedirectResponse
    {
        $product = CatalogProduct::query()->findOrFail($request->integer('id'));

        foreach ((array) $request->validated('options', []) as $row) {
            $option = $this->existingChild($product->options(), (int) ($row['id'] ?? 0));

            if (! empty($row['delete'])) {
                $option?->delete();

                continue;
            }

            if (blank($row['label'] ?? null) && blank($row['value'] ?? null)) {
                continue;
            }

            $option ??= new CatalogProductOption(['catalog_product_id' => $product->id, 'created_by' => $request->user()?->id]);
            $locale = ($row['locale'] ?? null) ?: app()->getLocale();
            $label = ($row['label'] ?? null) ?: __('Option label');
            $value = $row['value'] ?? null;

            $option->fill([
                'label' => $label,
                'label_translations' => [$locale => $label],
                'sort_order' => $product->options()->max('sort_order') + 1,
                'updated_by' => $request->user()?->id,
            ])->save();

            if (filled($value)) {
                CatalogProductOptionValue::query()->updateOrCreate(
                    [
                        'catalog_product_option_id' => $option->id,
                        'value' => $value,
                    ],
                    [
                        'value_translations' => [$locale => $value],
                        'sort_order' => $option->values()->max('sort_order') + 1,
                        'created_by' => $request->user()?->id,
                        'updated_by' => $request->user()?->id,
                    ],
                );
            }
        }

        flash(__('Product options saved.'))->success();

        return redirect()->route($this->routeName('options'), ['id' => $product->id]);
    }

    public function translations(Request $request): View
    {
        $product = $this->productForUtility($request);

        if (! $product) {
            return $this->utilityPlaceholder(__('Product translations'));
        }

        $product->load('translations');

        return view('admin.catalog.products.translations', [
            'product' => $product,
            'routeNames' => $this->routeNames(),
            'pageName' => __('Product translations'),
            'backUrl' => route($this->routeName('edit'), ['id' => $product->id]),
        ]);
    }

    public function saveTranslations(CatalogProductUtilityRequest $request): RedirectResponse
    {
        $product = CatalogProduct::query()->findOrFail($request->integer('id'));

        foreach ((array) $request->validated('translations', []) as $row) {
            $translation = $this->existingChild($product->translations(), (int) ($row['id'] ?? 0));

            if (! empty($row['delete'])) {
                $translation?->delete();

                continue;
            }

            if (blank($row['locale'] ?? null)) {
                continue;
            }

            if (! $translation && blank($row['title'] ?? null) && blank($row['subtitle'] ?? null) && blank($row['content'] ?? null)) {
                continue;
            }

            $translation ??= new CatalogProductTranslation(['catalog_product_id' => $product->id, 'created_by' => $request->user()?->id]);
            $translation->fill([
                'locale' => $row['locale'],
                'title' => $row['title'] ?? null,
                'subtitle' => $row['subtitle'] ?? null,
                'button_text' => $row['button_text'] ?? null,
                'link_url' => $row['link_url'] ?? null,
                'content' => $row['content'] ?? null,
                'updated_by' => $request->user()?->id,
            ])->save();
        }

        flash(__('Product translations saved.'))->success();

        return redirect()->route($this->routeName('translations'), ['id' => $product->id]);
    }

    public function videos(Request $request): View
    {
        $product = $this->productForUtility($request);

        if (! $product) {
            return $this->utilityPlaceholder(__('Product videos'));
        }

        $product->load('videos');

        return view('admin.catalog.products.videos', [
            'product' => $product,
            'routeNames' => $this->routeNames(),
            'pageName' => __('Product videos'),
            'backUrl' => route($this->routeName('edit'), ['id' => $product->id]),
        ]);
    }

    public function saveVideos(CatalogProductUtilityRequest $request): RedirectResponse
    {
        $product = CatalogProduct::query()->findOrFail($request->integer('id'));

        foreach ((array) $request->validated('videos', []) as $index => $row) {
            $video = $this->existingChild($product->videos(), (int) ($row['id'] ?? 0));

            if (! empty($row['delete']) || (blank($row['url'] ?? null) && $video)) {
                $video?->delete();

                continue;
            }

            if (blank($row['url'] ?? null)) {
                continue;
            }

            $video ??= new CatalogProductVideo(['catalog_product_id' => $product->id, 'created_by' => $request->user()?->id]);
            $video->fill([
                'title' => $row['title'] ?? null,
                'url' => $row['url'],
                'provider' => $row['provider'] ?? null,
                'sort_order' => $index + 1,
                'updated_by' => $request->user()?->id,
            ])->save();
        }

        flash(__('Product videos saved.'))->success();

        return redirect()->route($this->routeName('videos'), ['id' => $product->id]);
    }

    public function combinations(Request $request): View
    {
        $product = $this->productForUtility($request);

        if (! $product) {
            return $this->utilityPlaceholder(__('Product combinations'));
        }

        $product->load('combinationSets.products');

        return view('admin.catalog.products.combinations', [
            'product' => $product,
            'routeNames' => $this->routeNames(),
            'pageName' => __('Product combinations'),
            'backUrl' => route($this->routeName('edit'), ['id' => $product->id]),
        ]);
    }

    public function resetSortIndex(): View
    {
        return $this->utilityPlaceholder(__('Reset product sort order'));
    }

    private function productFromRequest(Request $request): ?CatalogProduct
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? CatalogProduct::query()->findOrFail($id) : null;
    }

    private function productForUtility(Request $request): ?CatalogProduct
    {
        $id = (int) ($request->route('id') ?: $request->integer('id') ?: $request->integer('catalog_product_id'));

        return $id > 0 ? CatalogProduct::query()->findOrFail($id) : null;
    }

    private function utilityPlaceholder(string $pageName): View
    {
        return view('admin.catalog.products.utility', [
            'pageName' => $pageName,
            'routeNames' => $this->routeNames(),
            'backUrl' => route($this->routeName('index')),
        ]);
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

    /**
     * @param  Collection<int, CatalogCategory>  $categories
     * @return list<int>
     */
    private function descendantIds(Collection $categories, int $parentId): array
    {
        $ids = [];

        foreach ($categories->where('parent_id', $parentId) as $category) {
            $ids[] = $category->id;
            $ids = [...$ids, ...$this->descendantIds($categories, $category->id)];
        }

        return $ids;
    }

    private function sortColumn(string $sort): string
    {
        return match ($sort) {
            'artikelnummer', 'sku' => 'sku',
            'naam', 'name' => 'name',
            'prijs', 'price' => 'price',
            'status' => 'status',
            default => 'id',
        };
    }

    private function normalizeStatus(mixed $status): ?string
    {
        return match ((string) $status) {
            '1', 'online', 'active', 'published' => 'published',
            '0', '2', 'offline', 'inactive', 'draft' => 'draft',
            'archived' => 'archived',
            '' => null,
            default => is_string($status) ? $status : null,
        };
    }

    private function shouldMoveProduct(Request $request): bool
    {
        return $request->integer('categoryId') > 0
            && $request->integer('move') > 0
            && in_array($request->string('direction')->toString(), ['up', 'down'], true);
    }

    private function moveProductWithinCategory(int $categoryId, int $productId, string $direction): void
    {
        $rows = DB::table('catalog_category_product')
            ->where('catalog_category_id', $categoryId)
            ->orderBy('sort_order')
            ->orderBy('catalog_product_id')
            ->get();

        $currentIndex = $rows->search(fn (object $row): bool => (int) $row->catalog_product_id === $productId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! isset($rows[$targetIndex])) {
            return;
        }

        $current = $rows[$currentIndex];
        $target = $rows[$targetIndex];

        DB::table('catalog_category_product')
            ->where('catalog_category_id', $categoryId)
            ->where('catalog_product_id', $current->catalog_product_id)
            ->update(['sort_order' => $target->sort_order]);

        DB::table('catalog_category_product')
            ->where('catalog_category_id', $categoryId)
            ->where('catalog_product_id', $target->catalog_product_id)
            ->update(['sort_order' => $current->sort_order]);
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
            'edit.tab' => $this->routeName('edit.tab'),
            'save' => $this->routeName('save'),
            'destroy' => $this->routeName('destroy'),
            'duplicate' => $this->routeName('duplicate'),
            'images' => $this->routeName('images'),
            'image.delete' => $this->routeName('image.delete'),
            'image.update-name' => $this->routeName('image.update-name'),
            'image.update-sort' => $this->routeName('image.update-sort'),
            'image.upload' => $this->routeName('image.upload'),
            'options' => $this->routeName('options'),
            'options.save' => $this->routeName('options.save'),
            'translations' => $this->routeName('translations'),
            'translations.save' => $this->routeName('translations.save'),
            'videos' => $this->routeName('videos'),
            'videos.save' => $this->routeName('videos.save'),
            'combinations' => $this->routeName('combinations'),
            'combination-sets.create' => 'admin.catalog.combination-sets.create',
            'combination-sets.edit' => 'admin.catalog.combination-sets.edit',
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.catalog.' : 'admin.catalog.').$name;
    }

    private function redirectToProductEdit(CatalogProduct $product, Request $request): RedirectResponse
    {
        if ($request->string('active_tab')->toString() === 'seo' && ! request()->routeIs('cms.*')) {
            return redirect()->route($this->routeName('edit.tab'), ['id' => $product->id, 'tab' => 'seo']);
        }

        return redirect()->route($this->routeName('edit'), ['id' => $product->id]);
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function uploadedFiles(mixed $files): array
    {
        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, fn (mixed $file): bool => $file instanceof UploadedFile));
    }

    private function existingChild(mixed $relation, int $id): mixed
    {
        return $id > 0 ? $relation->whereKey($id)->first() : null;
    }

    private function defaultCaptionForUpload(UploadedFile $file): string
    {
        return str(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            ->replace(['-', '_'], ' ')
            ->squish()
            ->title()
            ->toString();
    }
}
