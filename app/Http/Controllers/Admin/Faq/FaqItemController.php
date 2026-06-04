<?php

namespace App\Http\Controllers\Admin\Faq;

use App\Actions\Admin\Faq\DuplicateFaqItem;
use App\Actions\Admin\Faq\UpsertFaqItem;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\FaqItemRequest;
use App\Models\Cms\FaqCategory;
use App\Models\Cms\FaqItem;
use App\Models\Cms\NavigationMenuItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $query = FaqItem::query()->with('categories');

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
        $faqItem = $upsert->handle($request->validated(), $request->user());

        flash(__('FAQ item created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $faqItem->id]);
    }

    public function edit(Request $request): View
    {
        $faqItem = $this->faqItemFromRequest($request);
        $faqItem?->load('categories');

        return view('admin.faq.edit', [
            'faqItem' => $faqItem ?? new FaqItem([
                'status' => 'draft',
                'locale' => app()->getLocale(),
            ]),
            'categories' => $this->categories(),
            'navigationItems' => $this->navigationItems(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit FAQ item'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $faqItem ? route($this->routeName('destroy'), $faqItem) : null,
        ]);
    }

    public function save(FaqItemRequest $request, UpsertFaqItem $upsert): RedirectResponse
    {
        $faqItem = $upsert->handle($request->validated(), $request->user(), $request->faqItem());

        flash(__('FAQ item saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $faqItem->id]);
    }

    public function update(FaqItem $faqItem, FaqItemRequest $request, UpsertFaqItem $upsert): RedirectResponse
    {
        $faqItem = $upsert->handle($request->validated(), $request->user(), $faqItem);

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
            ->with('categories')
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

    private function faqItemFromRequest(Request $request): ?FaqItem
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

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
     * @return Collection<int, NavigationMenuItem>
     */
    private function navigationItems(): Collection
    {
        return NavigationMenuItem::query()
            ->with('menu')
            ->orderBy('navigation_menu_id')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('title')
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
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.faq.' : 'admin.faq.').$name;
    }
}
