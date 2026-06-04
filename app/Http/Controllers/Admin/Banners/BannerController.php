<?php

namespace App\Http\Controllers\Admin\Banners;

use App\Actions\Admin\Banners\DuplicateBanner;
use App\Actions\Admin\Banners\UpsertBanner;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Banners\BannerMediaRequest;
use App\Http\Requests\Admin\Banners\BannerRequest;
use App\Models\Cms\Banner;
use App\Models\Cms\BannerCategory;
use App\Support\Admin\Banners\BannerMediaManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BannerController extends Controller
{
    use UsesEditViewForCreate;

    /**
     * @var list<string>
     */
    private const EDIT_TABS = ['image', 'translations'];

    public function index(Request $request): View|RedirectResponse
    {
        if ($this->shouldMoveBanner($request)) {
            $this->moveBannerWithinCategory($request->integer('categoryId'), $request->integer('move') ?: $request->integer('id') ?: $request->integer('pos'), $request->string('direction')->toString());

            return redirect()->route($this->routeName('index'), $request->except(['move', 'pos', 'direction']));
        }

        $categories = $this->categories();
        $categoryId = $request->integer('categoryId');
        $query = Banner::query()
            ->with(['categories', 'translations']);

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('titel') || $request->filled('title')) {
            $title = $request->input('title', $request->input('titel'));
            $query->where(function ($query) use ($title): void {
                $query->where('title', 'like', '%'.$title.'%')
                    ->orWhereHas('translations', fn ($translationQuery) => $translationQuery->where('title', 'like', '%'.$title.'%'));
            });
        }

        if ($request->filled('link') || $request->filled('link_url')) {
            $link = $request->input('link_url', $request->input('link'));
            $query->where(function ($query) use ($link): void {
                $query->where('link_url', 'like', '%'.$link.'%')
                    ->orWhereHas('translations', fn ($translationQuery) => $translationQuery->where('link_url', 'like', '%'.$link.'%'));
            });
        }

        if ($request->filled('status') && $this->normalizeStatus($request->input('status')) !== null) {
            $query->where('status', $this->normalizeStatus($request->input('status')));
        }

        if ($categoryId > 0) {
            $categoryIds = [$categoryId];

            if ($request->boolean('showChild', true)) {
                $categoryIds = [...$categoryIds, ...$this->descendantIds($categories, $categoryId)];
            }

            $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('banner_categories.id', $categoryIds));
        }

        $banners = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'desc' ? 'desc' : 'asc')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.banners.index', [
            'banners' => $banners,
            'categories' => $categories,
            'selectedCategoryId' => $categoryId,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(BannerRequest $request, UpsertBanner $upsert): RedirectResponse
    {
        $banner = $upsert->handle(
            $request->validated(),
            $request->user(),
            null,
            $request->file('image') ?: $request->file('afbeelding'),
        );

        flash(__('Banner created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $banner->id]);
    }

    public function edit(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectQueryTabToRoute($request)) {
            return $redirect;
        }

        $banner = $this->bannerFromRequest($request);
        $banner?->load(['categories', 'translations']);
        $editableBanner = $banner ?? new Banner([
            'status' => 'draft',
            'starts_at' => now(),
            'metadata' => ['target' => '_self'],
        ]);
        $activeTab = $this->activeTab($request, $banner);

        return view('admin.banners.edit', [
            'banner' => $editableBanner,
            'activeTab' => $activeTab,
            'categories' => $this->categories(),
            'locales' => $this->locales(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit banner'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $banner ? route($this->routeName('destroy'), $banner) : null,
        ]);
    }

    public function save(BannerRequest $request, UpsertBanner $upsert): RedirectResponse
    {
        $banner = $upsert->handle(
            $request->validated(),
            $request->user(),
            $request->banner(),
            $request->file('image') ?: $request->file('afbeelding'),
        );

        flash(__('Banner saved.'))->success();

        $activeTab = $request->validated('active_tab');

        return redirect()
            ->route($this->editRouteName($activeTab), $this->editRedirectParameters($banner, $activeTab));
    }

    public function update(Banner $banner, BannerRequest $request, UpsertBanner $upsert): RedirectResponse
    {
        $banner = $upsert->handle(
            $request->validated(),
            $request->user(),
            $banner,
            $request->file('image') ?: $request->file('afbeelding'),
        );

        flash(__('Banner saved.'))->success();

        $activeTab = $request->validated('active_tab');

        return redirect()
            ->route($this->editRouteName($activeTab), $this->editRedirectParameters($banner, $activeTab));
    }

    public function bulkUploader(): View
    {
        return view('admin.banners.bulk-uploader', [
            'categories' => $this->categories(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Bulk banner uploader'),
            'backUrl' => route($this->routeName('index')),
        ]);
    }

    public function uploadBulk(BannerMediaRequest $request, BannerMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $files = $this->uploadedFiles($request);
        $categoryIds = $this->categoryIdsForBulkUpload($request);
        $created = [];

        foreach ($files as $file) {
            $banner = Banner::query()->create([
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'image_path' => $mediaManager->storeImage($file),
                'status' => 'draft',
                'starts_at' => now()->toDateString(),
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            if ($categoryIds !== []) {
                $banner->categories()->sync(
                    collect($categoryIds)->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index + 1]])->all()
                );
            }

            $created[] = $banner->id;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'jsonrpc' => '2.0',
                'status' => 'success',
                'ids' => $created,
                'result' => $created,
            ]);
        }

        flash(trans_choice(':count banner uploaded.|:count banners uploaded.', count($created), ['count' => count($created)]))->success();

        return redirect()->route($this->routeName('index'));
    }

    public function deleteImage(BannerMediaRequest $request, BannerMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $banner = Banner::query()->findOrFail($request->integer('bannerId') ?: $request->integer('id'));
        $mediaManager->deleteImage($banner, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success']);
        }

        flash(__('Banner image deleted.'))->success();

        return back();
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        flash(__('Banner deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    public function duplicate(BannerMediaRequest $request, DuplicateBanner $duplicate): JsonResponse|RedirectResponse
    {
        $id = $request->integer('itemId') ?: $request->integer('id');
        $banner = Banner::query()->with(['categories', 'translations'])->findOrFail($id);
        $copy = $duplicate->handle($banner, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'id' => $copy->id,
                'edit_url' => route($this->routeName('edit'), ['id' => $copy->id]),
            ]);
        }

        flash(__('Banner duplicated.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $copy->id]);
    }

    private function bannerFromRequest(Request $request): ?Banner
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? Banner::query()->findOrFail($id) : null;
    }

    private function activeTab(Request $request, ?Banner $banner): string
    {
        $tab = $request->route('tab') ?: $request->query('tab');

        if (! $banner instanceof Banner || ! is_string($tab) || ! in_array($tab, self::EDIT_TABS, true)) {
            return 'general';
        }

        return $tab;
    }

    /**
     * @return Collection<int, BannerCategory>
     */
    private function categories(): Collection
    {
        return BannerCategory::query()
            ->withCount('banners')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    private function locales(): array
    {
        return [
            'nl' => 'Nederlands',
            'en' => 'English',
            'de' => 'Deutsch',
            'fr' => 'Francais',
        ];
    }

    /**
     * @param  Collection<int, BannerCategory>  $categories
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
            'titel', 'title' => 'title',
            'status' => 'status',
            'startdatum', 'starts_at' => 'starts_at',
            'einddatum', 'ends_at' => 'ends_at',
            'sort_index', 'sort_order' => 'sort_order',
            default => 'id',
        };
    }

    private function normalizeStatus(mixed $status): ?string
    {
        return match ((string) $status) {
            '1', 'online', 'active', 'published' => 'published',
            '0', 'offline', 'inactive', 'draft' => 'draft',
            'archived' => 'archived',
            '2', '' => null,
            default => is_string($status) ? $status : null,
        };
    }

    private function shouldMoveBanner(Request $request): bool
    {
        return $request->integer('categoryId') > 0
            && ($request->integer('move') > 0 || $request->integer('id') > 0 || $request->integer('pos') > 0)
            && in_array($request->string('direction')->toString(), ['up', 'down'], true);
    }

    private function moveBannerWithinCategory(int $categoryId, int $bannerIdOrPosition, string $direction): void
    {
        $rows = DB::table('banner_category_banner')
            ->where('banner_category_id', $categoryId)
            ->orderBy('sort_order')
            ->orderBy('banner_id')
            ->get();

        $currentIndex = $rows->search(fn (object $row): bool => (int) $row->banner_id === $bannerIdOrPosition);

        if ($currentIndex === false) {
            $currentIndex = $bannerIdOrPosition - 1;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! isset($rows[$currentIndex], $rows[$targetIndex])) {
            return;
        }

        $current = $rows[$currentIndex];
        $target = $rows[$targetIndex];

        DB::table('banner_category_banner')
            ->where('banner_category_id', $categoryId)
            ->where('banner_id', $current->banner_id)
            ->update(['sort_order' => $target->sort_order]);

        DB::table('banner_category_banner')
            ->where('banner_category_id', $categoryId)
            ->where('banner_id', $target->banner_id)
            ->update(['sort_order' => $current->sort_order]);
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function uploadedFiles(BannerMediaRequest $request): array
    {
        $files = $request->file('banners') ?: $request->file('file') ?: $request->file('image');

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        return is_array($files)
            ? array_values(array_filter($files, fn (mixed $file): bool => $file instanceof UploadedFile))
            : [];
    }

    /**
     * @return list<int>
     */
    private function categoryIdsForBulkUpload(BannerMediaRequest $request): array
    {
        if ($request->filled('cat')) {
            return collect(explode(',', (string) $request->input('cat')))
                ->map(fn (string $id): int => (int) trim($id))
                ->filter()
                ->values()
                ->all();
        }

        return collect($request->input('categories', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->values()
            ->all();
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
            'edit.tab' => request()->routeIs('cms.*') ? $this->routeName('edit') : $this->routeName('edit.tab'),
            'save' => $this->routeName('save'),
            'destroy' => $this->routeName('destroy'),
            'duplicate' => $this->routeName('duplicate'),
            'bulk' => $this->routeName('bulk'),
            'bulk.upload' => $this->routeName('bulk.upload'),
            'image.delete' => $this->routeName('image.delete'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.banners.' : 'admin.banners.').$name;
    }

    private function editRouteName(?string $activeTab = null): string
    {
        if (! request()->routeIs('cms.*') && in_array($activeTab, self::EDIT_TABS, true)) {
            return $this->routeName('edit.tab');
        }

        return $this->routeName('edit');
    }

    /**
     * @return array<string, mixed>
     */
    private function editRedirectParameters(Banner $banner, ?string $activeTab = null): array
    {
        $parameters = ['id' => $banner->id];

        if (in_array($activeTab, self::EDIT_TABS, true)) {
            $parameters['tab'] = $activeTab;
        }

        return $parameters;
    }

    private function redirectQueryTabToRoute(Request $request): ?RedirectResponse
    {
        if (request()->routeIs('cms.*') || $request->route('tab')) {
            return null;
        }

        $tab = $request->query('tab');

        if (! is_string($tab) || ! in_array($tab, self::EDIT_TABS, true)) {
            return null;
        }

        $id = (int) ($request->route('id') ?: $request->integer('id'));

        if ($id < 1) {
            return null;
        }

        return redirect()->route($this->routeName('edit.tab'), [
            'id' => $id,
            'tab' => $tab,
        ]);
    }
}
