<?php

namespace App\Http\Controllers\Admin\Content;

use App\Actions\Admin\Content\DuplicateContentItem;
use App\Actions\Admin\Content\UpsertContentItem;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\ContentActionRequest;
use App\Http\Requests\Admin\Content\ContentItemRequest;
use App\Http\Requests\Admin\Content\ContentMediaRequest;
use App\Http\Requests\Admin\Content\ContentSliderRequest;
use App\Models\Cms\ContentCategory;
use App\Models\Cms\ContentImage;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Form;
use App\Models\Cms\SliderCategory;
use App\Support\Admin\Content\ContentMediaManager;
use App\Support\Content\ContentPreviewLinkIssuer;
use App\Support\Localization\TranslationRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContentItemController extends Controller
{
    use UsesEditViewForCreate;

    /**
     * @var list<string>
     */
    private const EDIT_TABS = ['seo', 'form'];

    public function index(Request $request): View|RedirectResponse
    {
        if ($this->shouldMoveItem($request)) {
            $this->moveItemWithinCategory($request->integer('categoryId'), $request->integer('move'), $request->string('direction')->toString());

            return redirect()->route($this->routeName('index'), $request->except(['move', 'direction']));
        }

        return view('admin.content.index', [
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(ContentItemRequest $request, UpsertContentItem $upsert): RedirectResponse
    {
        $contentItem = $upsert->handle(
            $request->validated(),
            $request->user(),
            null,
            $request->file('attachment_files', []),
        );

        flash(__('Page created.'))->success();

        return redirect()
            ->route($this->routeName('edit'), ['id' => $contentItem->id]);
    }

    public function edit(Request $request, TranslationRepository $translations): View|RedirectResponse
    {
        if ($redirect = $this->redirectQueryTabToRoute($request)) {
            return $redirect;
        }

        $contentItem = $this->contentItemFromRequest($request);

        $contentItem?->load([
            'categories',
            'attachments',
        ]);

        $editableContentItem = $contentItem ?? new ContentItem([
            'status' => 'draft',
            'locale' => app()->getLocale(),
            'active_from' => now(),
        ]);
        $frontendUrl = $contentItem?->slug
            ? $this->frontendUrl($contentItem, $translations)
            : null;

        return view('admin.content.edit', [
            'contentItem' => $editableContentItem,
            'frontendUrl' => $frontendUrl,
            'frontendUrlBase' => $this->frontendUrlBase($editableContentItem->locale, $translations),
            'categories' => $this->categories(),
            'forms' => Form::query()->orderBy('name')->get(),
            'sliderCategories' => SliderCategory::query()->orderBy('name')->get(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit page'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $contentItem ? route($this->routeName('destroy'), $contentItem) : null,
        ]);
    }

    public function save(ContentItemRequest $request, UpsertContentItem $upsert): RedirectResponse
    {
        $contentItem = $upsert->handle(
            $request->validated(),
            $request->user(),
            $request->contentItem(),
            $request->file('attachment_files', []),
        );

        flash(__('Page saved.'))->success();

        $activeTab = $request->validated('active_tab');

        return redirect()
            ->route($this->editRouteName($activeTab), $this->editRedirectParameters($contentItem, $activeTab));
    }

    public function destroy(ContentItem $contentItem): RedirectResponse
    {
        $contentItem->delete();

        flash(__('Page deleted.'))->success();

        return redirect()
            ->route($this->routeName('index'));
    }

    public function preview(Request $request, ContentPreviewLinkIssuer $issuer): RedirectResponse
    {
        $contentItem = $this->contentItemFromRequest($request);

        abort_unless($contentItem, 404);

        return redirect()->to($issuer->issue($contentItem, $request, $request->user()));
    }

    public function images(Request $request): View
    {
        $contentItem = $this->contentItemFromRequest($request);
        $contentItem?->load('images');

        return view('admin.content.images', [
            'contentItem' => $contentItem,
            'routeNames' => $this->routeNames(),
            'pageName' => __('Page images'),
            'backUrl' => route($this->routeName('index')),
        ]);
    }

    public function uploadImage(ContentMediaRequest $request, ContentMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $contentItem = ContentItem::query()->findOrFail((int) ($request->route('id') ?: $request->integer('id')));
        $files = collect([$request->file('file'), $request->file('image')])
            ->filter()
            ->merge($request->file('images', []))
            ->values();

        abort_unless($files->isNotEmpty(), 422);

        $images = $files->map(function ($file) use ($request, $contentItem, $mediaManager): ContentImage {
            $caption = $request->string('caption')->toString() ?: $this->defaultCaptionForUpload($file);

            return $mediaManager->storeItemImage($contentItem, $file, $caption, $request->user(), [
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

    public function updateImageName(ContentMediaRequest $request): JsonResponse|RedirectResponse
    {
        $image = ContentImage::query()->findOrFail($request->integer('uploadId') ?: $request->integer('id'));
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

        $mediaManager->updateSortOrder(ContentImage::class, $ids, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Sort order updated.'))->success();

            return back();
        }

        return response()->json(['status' => 'success']);
    }

    public function deleteImage(ContentMediaRequest $request, ContentMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $image = ContentImage::query()->findOrFail($request->integer('id'));
        $mediaManager->deleteMedia($image, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Image deleted.'))->success();

            return back();
        }

        return response()->json(['status' => 'success']);
    }

    public function slider(Request $request): View
    {
        $contentItem = $this->contentItemFromRequest($request);

        return view('admin.content.slider', [
            'contentItem' => $contentItem,
            'sliderCategories' => SliderCategory::query()->orderBy('name')->get(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Page slider'),
            'backUrl' => route($this->routeName('index')),
        ]);
    }

    public function saveSlider(ContentSliderRequest $request, UpsertContentItem $upsert): RedirectResponse
    {
        $contentItem = $request->contentItem();

        abort_unless($contentItem, 404);

        $data = [
            ...$contentItem->only([
                'title',
                'subtitle',
                'slug',
                'locale',
                'meta_description',
                'status',
                'active_from',
                'active_until',
                'form_id',
            ]),
            'slider_category_id' => $request->validated('slider_category_id'),
            'categories' => $contentItem->categories()->pluck('content_categories.id')->all(),
        ];

        $upsert->handle($data, $request->user(), $contentItem);

        flash(__('Slider settings saved.'))->success();

        return redirect()
            ->route($this->routeName('slider'), ['id' => $contentItem->id]);
    }

    public function duplicate(ContentActionRequest $request, DuplicateContentItem $duplicate): JsonResponse|RedirectResponse
    {
        $id = $request->integer('itemId') ?: $request->integer('item_id') ?: $request->integer('id');
        $contentItem = ContentItem::query()->with(['categories', 'attachments', 'images'])->findOrFail($id);
        $copy = $duplicate->handle($contentItem, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Page duplicated.'))->success();

            return redirect()
                ->route($this->routeName('edit'), ['id' => $copy->id]);
        }

        return response()->json([
            'status' => 'success',
            'id' => $copy->id,
            'edit_url' => route($this->routeName('edit'), ['id' => $copy->id]),
        ]);
    }

    private function contentItemFromRequest(Request $request): ?ContentItem
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? ContentItem::query()->findOrFail($id) : null;
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

    private function shouldMoveItem(Request $request): bool
    {
        return $request->integer('categoryId') > 0
            && $request->integer('move') > 0
            && in_array($request->string('direction')->toString(), ['up', 'down'], true);
    }

    private function moveItemWithinCategory(int $categoryId, int $contentItemId, string $direction): void
    {
        $rows = DB::table('content_category_content_item')
            ->where('content_category_id', $categoryId)
            ->orderBy('sort_order')
            ->orderBy('content_item_id')
            ->get();

        $currentIndex = $rows->search(fn (object $row): bool => (int) $row->content_item_id === $contentItemId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! isset($rows[$targetIndex])) {
            return;
        }

        $current = $rows[$currentIndex];
        $target = $rows[$targetIndex];

        DB::table('content_category_content_item')
            ->where('content_category_id', $categoryId)
            ->where('content_item_id', $current->content_item_id)
            ->update(['sort_order' => $target->sort_order]);

        DB::table('content_category_content_item')
            ->where('content_category_id', $categoryId)
            ->where('content_item_id', $target->content_item_id)
            ->update(['sort_order' => $current->sort_order]);
    }

    private function frontendUrl(ContentItem $contentItem, TranslationRepository $translations): string
    {
        $locale = $translations->normalizeLocale($contentItem->locale ?: app()->getLocale());

        if ($locale !== $translations->defaultLocale()) {
            return route('frontend.locale.pages.show', [
                'locale' => $locale,
                'slug' => $contentItem->slug,
            ]);
        }

        return route('frontend.pages.show', [
            'slug' => $contentItem->slug,
        ]);
    }

    private function frontendUrlBase(?string $locale, TranslationRepository $translations): string
    {
        $locale = $translations->normalizeLocale($locale ?: app()->getLocale());
        $baseUrl = $locale !== $translations->defaultLocale()
            ? url($locale)
            : url('/');

        return rtrim($baseUrl, '/').'/';
    }

    private function defaultCaptionForUpload(mixed $file): ?string
    {
        if (! method_exists($file, 'getClientOriginalName')) {
            return null;
        }

        return str(pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME))
            ->replace(['-', '_'], ' ')
            ->squish()
            ->title()
            ->toString();
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
            'images' => $this->routeName('images'),
            'images.upload' => $this->routeName('images.upload'),
            'slider' => $this->routeName('slider'),
            'slider.save' => $this->routeName('slider.save'),
            'preview' => $this->routeName('preview'),
            'duplicate' => $this->routeName('duplicate'),
            'image.delete' => $this->routeName('image.delete'),
            'image.update-name' => $this->routeName('image.update-name'),
            'image.update-sort' => $this->routeName('image.update-sort'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.content.' : 'admin.content.').$name;
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
    private function editRedirectParameters(ContentItem $contentItem, ?string $activeTab = null): array
    {
        $parameters = ['id' => $contentItem->id];

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

        if ($id <= 0) {
            return null;
        }

        return redirect()->route($this->routeName('edit.tab'), [
            'id' => $id,
            'tab' => $tab,
        ]);
    }
}
