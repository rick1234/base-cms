<?php

namespace App\Livewire\Admin;

use App\Models\Cms\ContentCategory;
use App\Models\Cms\ContentItem;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ListingOverview extends Component
{
    use WithPagination;

    public string $module = 'content';

    public string $filterId = '';

    public string $filterTitle = '';

    public string $filterLocale = '';

    public string $filterStatus = '';

    public int $filterCategoryId = 0;

    public bool $showChild = false;

    public string $sort = 'id';

    public string $direction = 'desc';

    public string $draftId = '';

    public string $draftTitle = '';

    public string $draftLocale = '';

    public string $draftStatus = '';

    public int $draftCategoryId = 0;

    public bool $draftShowChild = false;

    public int $perPage = 25;

    public bool $usesCmsRoutes = false;

    public bool $categorySelectorOpen = false;

    /**
     * @var array<string, mixed>
     */
    protected array $queryString = [
        'filterId' => ['as' => 'id', 'except' => ''],
        'filterTitle' => ['as' => 'title', 'except' => ''],
        'filterLocale' => ['as' => 'locale', 'except' => ''],
        'filterStatus' => ['as' => 'status', 'except' => ''],
        'filterCategoryId' => ['as' => 'categoryId', 'except' => 0],
        'showChild' => ['except' => false],
        'sort' => ['except' => 'id'],
        'direction' => ['as' => 'sorttype', 'except' => 'desc'],
    ];

    public function mount(string $module = 'content'): void
    {
        $this->module = $module;
        $this->usesCmsRoutes = request()->routeIs('cms.*');

        $this->filterTitle = $this->filterTitle ?: request()->string('titel')->toString();
        $this->filterLocale = $this->filterLocale ?: request()->string('tc')->toString();
        $this->direction = $this->normalizeDirection($this->direction ?: request()->string('sorttype')->toString());
        $this->sort = $this->normalizeSort($this->sort ?: request()->string('sort')->toString());

        $this->syncDraftsFromFilters();
    }

    public function applyFilters(): void
    {
        $this->filterId = trim($this->draftId);
        $this->filterTitle = trim($this->draftTitle);
        $this->filterLocale = trim($this->draftLocale);
        $this->filterStatus = trim($this->draftStatus);
        $this->filterCategoryId = max(0, $this->draftCategoryId);
        $this->showChild = $this->draftShowChild && $this->filterCategoryId > 0;

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filterId = '';
        $this->filterTitle = '';
        $this->filterLocale = '';
        $this->filterStatus = '';
        $this->filterCategoryId = 0;
        $this->showChild = false;

        $this->syncDraftsFromFilters();
        $this->resetPage();
    }

    public function openCategorySelector(): void
    {
        $this->draftCategoryId = $this->filterCategoryId;
        $this->draftShowChild = $this->showChild;
        $this->categorySelectorOpen = true;
    }

    public function closeCategorySelector(): void
    {
        $this->categorySelectorOpen = false;
    }

    public function selectCategory(int $categoryId): void
    {
        $this->draftCategoryId = max(0, $categoryId);
        $this->applyCategoryFilter();
    }

    public function clearCategory(): void
    {
        $this->draftCategoryId = 0;
        $this->draftShowChild = false;
        $this->applyCategoryFilter();
    }

    public function updatedDraftShowChild(): void
    {
        if ($this->filterCategoryId > 0 && $this->filterCategoryId === $this->draftCategoryId) {
            $this->applyCategoryFilter(closeSelector: false);
        }
    }

    public function sortBy(string $column, string $direction = 'desc'): void
    {
        $this->sort = $this->normalizeSort($column);
        $this->direction = $this->normalizeDirection($direction);

        $this->resetPage();
    }

    public function moveItem(int $contentItemId, string $direction): void
    {
        if ($this->module !== 'content' || $this->filterCategoryId < 1 || $this->showChild) {
            return;
        }

        $this->moveContentItemWithinCategory($this->filterCategoryId, $contentItemId, $direction);
    }

    public function render(): View
    {
        $categories = $this->contentCategories();

        return view('livewire.admin.listing-overview', [
            'categories' => $categories,
            'categoriesByParent' => $categories->groupBy(fn (ContentCategory $category): int => $category->parent_id ?: 0),
            'items' => $this->contentItems($categories),
            'localeOptions' => ['nl', 'en'],
            'routeNames' => $this->routeNames(),
            'selectedCategoryName' => $this->selectedCategoryName($categories),
        ]);
    }

    private function syncDraftsFromFilters(): void
    {
        $this->draftId = $this->filterId;
        $this->draftTitle = $this->filterTitle;
        $this->draftLocale = $this->filterLocale;
        $this->draftStatus = $this->filterStatus;
        $this->draftCategoryId = $this->filterCategoryId;
        $this->draftShowChild = $this->showChild;
    }

    private function applyCategoryFilter(bool $closeSelector = true): void
    {
        $this->filterCategoryId = max(0, $this->draftCategoryId);
        $this->showChild = $this->draftShowChild && $this->filterCategoryId > 0;

        if ($this->filterCategoryId < 1) {
            $this->draftShowChild = false;
        }

        if ($closeSelector) {
            $this->categorySelectorOpen = false;
        }

        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, ContentItem>
     */
    private function contentItems(Collection $categories): LengthAwarePaginator
    {
        $query = ContentItem::query()
            ->with('categories')
            ->withCount(['attachments', 'images']);

        if ($this->filterId !== '') {
            $query->whereKey((int) $this->filterId);
        }

        if ($this->filterTitle !== '') {
            $query->where('title', 'like', '%'.$this->filterTitle.'%');
        }

        if ($this->filterStatus !== '') {
            $query->where('status', $this->normalizeStatus($this->filterStatus));
        }

        if ($this->filterLocale !== '') {
            $query->where('locale', $this->filterLocale);
        }

        if ($this->filterCategoryId > 0) {
            $categoryIds = [$this->filterCategoryId];

            if ($this->showChild) {
                $categoryIds = [...$categoryIds, ...$this->descendantIds($categories, $this->filterCategoryId)];
            }

            $query->whereHas(
                'categories',
                fn (Builder $categoryQuery): Builder => $categoryQuery->whereIn('content_categories.id', $categoryIds)
            );
        }

        return $query
            ->orderBy($this->sortColumn(), $this->direction)
            ->orderByDesc('id')
            ->paginate($this->perPage);
    }

    /**
     * @return Collection<int, ContentCategory>
     */
    private function contentCategories(): Collection
    {
        return ContentCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, ContentCategory>  $categories
     */
    private function selectedCategoryName(Collection $categories): string
    {
        if ($this->draftCategoryId < 1) {
            return __('Selecteer');
        }

        return (string) ($categories->firstWhere('id', $this->draftCategoryId)?->name ?? __('Onbekende categorie'));
    }

    /**
     * @param  Collection<int, ContentCategory>  $categories
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

    private function moveContentItemWithinCategory(int $categoryId, int $contentItemId, string $direction): void
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            return;
        }

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

    private function sortColumn(): string
    {
        return match ($this->sort) {
            'title' => 'title',
            'locale' => 'locale',
            'status' => 'status',
            'sort-order' => 'sort_order',
            default => 'id',
        };
    }

    private function normalizeSort(string $sort): string
    {
        return match ($sort) {
            'titel', 'title' => 'title',
            'taalcode', 'locale' => 'locale',
            'status' => 'status',
            'sort_index', 'sort_order', 'sort-order' => 'sort-order',
            default => 'id',
        };
    }

    private function normalizeDirection(string $direction): string
    {
        return strtolower($direction) === 'asc' ? 'asc' : 'desc';
    }

    private function normalizeStatus(mixed $status): string
    {
        return match ((string) $status) {
            '3', 'online', 'active', 'published' => 'published',
            '4', 'offline', 'inactive', 'draft' => 'draft',
            default => (string) $status,
        };
    }

    /**
     * @return array<string, string>
     */
    private function routeNames(): array
    {
        $prefix = $this->usesCmsRoutes ? 'cms.content.' : 'admin.content.';

        return [
            'edit' => $prefix.'edit',
            'duplicate' => $prefix.'duplicate',
            'destroy' => $prefix.'destroy',
        ];
    }
}
