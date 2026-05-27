<?php

namespace App\Http\Controllers\Admin\Faq;

use App\Actions\Admin\Faq\DuplicateFaqItem;
use App\Actions\Admin\Faq\UpsertFaqItem;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\FaqItemRequest;
use App\Http\Requests\Admin\Faq\FaqMediaRequest;
use App\Http\Requests\Admin\Faq\FaqVideoRequest;
use App\Models\Cms\FaqCategory;
use App\Models\Cms\FaqImage;
use App\Models\Cms\FaqItem;
use App\Models\Cms\FaqVideo;
use App\Support\Admin\Faq\FaqMediaManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FaqItemController extends Controller
{
    use UsesEditViewForCreate;

    public function index(Request $request): View|RedirectResponse
    {
        if ($this->shouldMoveFaqItem($request)) {
            $this->moveFaqItemWithinCategory($request->integer('categoryId'), $request->integer('move') ?: $request->integer('id'), $request->string('direction')->toString());

            return redirect()->route($this->routeName('index'), $request->except(['move', 'direction']));
        }

        $categories = $this->categories();
        $categoryId = $request->integer('categoryId');
        $query = FaqItem::query()
            ->with('categories')
            ->withCount(['attachments', 'images', 'videos']);

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('question') || $request->filled('vraag')) {
            $query->where('question', 'like', '%'.$request->input('question', $request->input('vraag')).'%');
        }

        if ($request->filled('status') && $this->normalizeStatus($request->input('status')) !== null) {
            $query->where('status', $this->normalizeStatus($request->input('status')));
        }

        if ($categoryId > 0) {
            $categoryIds = [$categoryId];

            if ($request->boolean('showChild')) {
                $categoryIds = [...$categoryIds, ...$this->descendantIds($categories, $categoryId)];
            }

            $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('faq_categories.id', $categoryIds));
        }

        $faqItems = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.faq.index', [
            'faqItems' => $faqItems,
            'categories' => $categories,
            'selectedCategoryId' => $categoryId,
            'showChild' => $request->boolean('showChild'),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(FaqItemRequest $request, UpsertFaqItem $upsert): RedirectResponse
    {
        $faqItem = $upsert->handle(
            $request->validated(),
            $request->user(),
            null,
            $this->uploadedFiles($request->file('attachment_files') ?: $request->file('attachment')),
        );

        flash(__('FAQ item created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $faqItem->id]);
    }

    public function edit(Request $request): View
    {
        $faqItem = $this->faqItemFromRequest($request);
        $faqItem?->load(['attachments', 'categories', 'images', 'videos']);

        return view('admin.faq.edit', [
            'faqItem' => $faqItem ?? new FaqItem([
                'status' => 'draft',
                'locale' => app()->getLocale(),
                'active_from' => now(),
            ]),
            'categories' => $this->categories(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit FAQ item'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $faqItem ? route($this->routeName('destroy'), $faqItem) : null,
        ]);
    }

    public function save(FaqItemRequest $request, UpsertFaqItem $upsert): RedirectResponse
    {
        $faqItem = $upsert->handle(
            $request->validated(),
            $request->user(),
            $request->faqItem(),
            $this->uploadedFiles($request->file('attachment_files') ?: $request->file('attachment')),
        );

        flash(__('FAQ item saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $faqItem->id]);
    }

    public function update(FaqItem $faqItem, FaqItemRequest $request, UpsertFaqItem $upsert): RedirectResponse
    {
        $faqItem = $upsert->handle(
            $request->validated(),
            $request->user(),
            $faqItem,
            $this->uploadedFiles($request->file('attachment_files') ?: $request->file('attachment')),
        );

        flash(__('FAQ item saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $faqItem->id]);
    }

    public function destroy(FaqItem $faqItem): RedirectResponse
    {
        $faqItem->delete();

        flash(__('FAQ item deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    public function duplicate(Request $request, DuplicateFaqItem $duplicate): JsonResponse|RedirectResponse
    {
        $id = $request->integer('itemId') ?: $request->integer('item_id') ?: $request->integer('id');
        $faqItem = FaqItem::query()
            ->with(['attachments', 'categories', 'images', 'videos'])
            ->findOrFail($id);

        $copy = $duplicate->handle($faqItem, $request->user());

        if (! $request->expectsJson()) {
            flash(__('FAQ item duplicated.'))->success();

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
        $faqItem = $this->faqItemForUtility($request);

        if (! $faqItem) {
            return $this->utilityPlaceholder(__('FAQ images'));
        }

        $faqItem->load('images');

        return view('admin.faq.images', [
            'faqItem' => $faqItem,
            'routeNames' => $this->routeNames(),
            'pageName' => __('FAQ images'),
            'backUrl' => route($this->routeName('edit'), ['id' => $faqItem->id]),
        ]);
    }

    public function uploadImage(FaqMediaRequest $request, FaqMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $faqItem = FaqItem::query()->findOrFail($request->integer('id') ?: $request->integer('faq_item_id'));
        $file = $request->file('file') ?: $request->file('image');

        abort_unless($file instanceof UploadedFile, 422);

        $image = $mediaManager->storeImage($faqItem, $file, $request->string('caption')->toString() ?: null, $request->user());

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

    public function updateImageName(FaqMediaRequest $request): JsonResponse|RedirectResponse
    {
        $image = FaqImage::query()->findOrFail($request->integer('uploadId') ?: $request->integer('id'));
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

    public function updateImageSort(FaqMediaRequest $request, FaqMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $ids = collect(explode(',', (string) $request->input('sort_index')))
            ->map(fn (string $id): int => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $mediaManager->updateSortOrder(FaqImage::class, $ids, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Sort order updated.'))->success();

            return back();
        }

        return response()->json(['status' => 'success']);
    }

    public function deleteImage(FaqMediaRequest $request, FaqMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $image = FaqImage::query()->findOrFail($request->integer('id'));
        $mediaManager->deleteMedia($image, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Image deleted.'))->success();

            return back();
        }

        return response()->json(['status' => 'success']);
    }

    public function videos(Request $request): View
    {
        $faqItem = $this->faqItemForUtility($request);

        if (! $faqItem) {
            return $this->utilityPlaceholder(__('FAQ videos'));
        }

        $faqItem->load('videos');

        return view('admin.faq.videos', [
            'faqItem' => $faqItem,
            'routeNames' => $this->routeNames(),
            'pageName' => __('FAQ videos'),
            'backUrl' => route($this->routeName('edit'), ['id' => $faqItem->id]),
        ]);
    }

    public function saveVideos(FaqVideoRequest $request): RedirectResponse
    {
        $faqItem = FaqItem::query()->findOrFail($request->integer('id'));

        foreach ((array) $request->validated('videos', []) as $index => $row) {
            $video = $row['id'] ?? null
                ? $faqItem->videos()->whereKey((int) $row['id'])->first()
                : null;

            if (! empty($row['delete']) || (blank($row['url'] ?? null) && $video)) {
                $video?->delete();

                continue;
            }

            if (blank($row['url'] ?? null)) {
                continue;
            }

            $video ??= new FaqVideo(['faq_item_id' => $faqItem->id, 'created_by' => $request->user()?->id]);
            $video->fill([
                'title' => $row['title'] ?? null,
                'url' => $row['url'],
                'provider' => $row['provider'] ?? $this->videoProvider($row['url']),
                'sort_order' => $index + 1,
                'updated_by' => $request->user()?->id,
            ])->save();
        }

        flash(__('FAQ videos saved.'))->success();

        return redirect()->route($this->routeName('videos'), ['id' => $faqItem->id]);
    }

    public function deleteVideo(Request $request): JsonResponse|RedirectResponse
    {
        $video = FaqVideo::query()->findOrFail($request->integer('videoid') ?: $request->integer('video_id') ?: $request->integer('id'));
        $video->delete();

        if (! $request->expectsJson()) {
            flash(__('Video deleted.'))->success();

            return back();
        }

        return response()->json(['status' => 'success']);
    }

    private function faqItemFromRequest(Request $request): ?FaqItem
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? FaqItem::query()->findOrFail($id) : null;
    }

    private function faqItemForUtility(Request $request): ?FaqItem
    {
        $id = (int) ($request->route('id') ?: $request->integer('id') ?: $request->integer('faq_item_id'));

        return $id > 0 ? FaqItem::query()->findOrFail($id) : null;
    }

    /**
     * @return Collection<int, FaqCategory>
     */
    private function categories(): Collection
    {
        return FaqCategory::query()
            ->withCount('faqItems')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, FaqCategory>  $categories
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
            'vraag', 'question', 'naam' => 'question',
            'status' => 'status',
            'sort_index', 'sort_order' => 'sort_order',
            default => 'id',
        };
    }

    private function normalizeStatus(mixed $status): ?string
    {
        return match ((string) $status) {
            '1', 'online', 'active', 'published' => 'published',
            '0', '2', '3', 'offline', 'inactive', 'draft' => 'draft',
            'archived' => 'archived',
            '' => null,
            default => is_string($status) ? $status : null,
        };
    }

    private function shouldMoveFaqItem(Request $request): bool
    {
        return $request->integer('categoryId') > 0
            && ($request->integer('move') > 0 || $request->integer('id') > 0)
            && in_array($request->string('direction')->toString(), ['up', 'down'], true);
    }

    private function moveFaqItemWithinCategory(int $categoryId, int $faqItemId, string $direction): void
    {
        $rows = DB::table('faq_category_faq_item')
            ->where('faq_category_id', $categoryId)
            ->orderBy('sort_order')
            ->orderBy('faq_item_id')
            ->get();

        $currentIndex = $rows->search(fn (object $row): bool => (int) $row->faq_item_id === $faqItemId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! isset($rows[$targetIndex])) {
            return;
        }

        $current = $rows[$currentIndex];
        $target = $rows[$targetIndex];

        DB::table('faq_category_faq_item')
            ->where('faq_category_id', $categoryId)
            ->where('faq_item_id', $current->faq_item_id)
            ->update(['sort_order' => $target->sort_order]);

        DB::table('faq_category_faq_item')
            ->where('faq_category_id', $categoryId)
            ->where('faq_item_id', $target->faq_item_id)
            ->update(['sort_order' => $current->sort_order]);
    }

    private function videoProvider(string $url): ?string
    {
        if (str_contains($url, 'youtu')) {
            return 'youtube';
        }

        if (str_contains($url, 'vimeo')) {
            return 'vimeo';
        }

        return null;
    }

    private function utilityPlaceholder(string $pageName): View
    {
        return view('admin.faq.utility', [
            'pageName' => $pageName,
            'routeNames' => $this->routeNames(),
            'backUrl' => route($this->routeName('index')),
        ]);
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
            'duplicate' => $this->routeName('duplicate'),
            'images' => $this->routeName('images'),
            'image.delete' => $this->routeName('image.delete'),
            'image.update-name' => $this->routeName('image.update-name'),
            'image.update-sort' => $this->routeName('image.update-sort'),
            'image.upload' => $this->routeName('image.upload'),
            'videos' => $this->routeName('videos'),
            'videos.save' => $this->routeName('videos.save'),
            'video.delete' => $this->routeName('video.delete'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.faq.' : 'admin.faq.').$name;
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
}
