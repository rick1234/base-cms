<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Component;

class CategoryTreeManager extends Component
{
    public string $module;

    public ?int $selectedCategoryId = null;

    public ?int $draggedCategoryId = null;

    public ?string $message = null;

    public function mount(string $module): void
    {
        abort_unless(array_key_exists($module, config('cms_categories', [])), 404);

        $this->module = $module;
        $selectedId = (int) request('categoryId', request('node_id', 0));
        $this->selectedCategoryId = $selectedId > 0 ? $selectedId : null;
    }

    public function selectCategory(int $categoryId): void
    {
        if ($this->categories()->contains('id', $categoryId)) {
            $this->selectedCategoryId = $categoryId;
        }
    }

    public function clearSelection(): void
    {
        $this->selectedCategoryId = null;
    }

    public function moveCategory(int $targetCategoryId, ?int $draggedCategoryId = null, string $position = 'before'): void
    {
        $draggedCategoryId ??= $this->draggedCategoryId;

        if (! $draggedCategoryId || $draggedCategoryId === $targetCategoryId) {
            $this->draggedCategoryId = null;

            return;
        }

        $categories = $this->categories();
        $dragged = $categories->firstWhere('id', $draggedCategoryId);
        $target = $categories->firstWhere('id', $targetCategoryId);

        if (! $dragged || ! $target) {
            $this->draggedCategoryId = null;

            return;
        }

        if ((int) $dragged->parent_id !== (int) $target->parent_id) {
            $this->message = __('Categorieen kunnen alleen binnen hetzelfde niveau worden gesorteerd.');
            $this->draggedCategoryId = null;

            return;
        }

        $ids = $categories
            ->where('parent_id', $dragged->parent_id)
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->values()
            ->all();

        $ids = $this->sortedIdsAfterMove($ids, (int) $dragged->id, (int) $target->id, $position);

        if ($ids === null) {
            $this->draggedCategoryId = null;

            return;
        }

        foreach ($ids as $index => $id) {
            $this->modelClass()::query()
                ->whereKey($id)
                ->update(['sort_order' => $index + 1]);
        }

        $this->selectedCategoryId = $dragged->id;
        $this->draggedCategoryId = null;
        $this->message = __('Categorie volgorde opgeslagen.');
    }

    public function render(): View
    {
        $categories = $this->categories();
        $selectedCategory = $this->selectedCategory($categories);

        return view('livewire.admin.category-tree-manager', [
            'categories' => $categories,
            'categoriesByParent' => $categories->groupBy(fn (Model $category): int => $category->parent_id ?: 0),
            'selectedCategory' => $selectedCategory,
            'selectedUrl' => $selectedCategory ? $this->categoryUrl($selectedCategory) : null,
            'linkedItems' => $selectedCategory ? $this->linkedItems($selectedCategory) : collect(),
            'linkedCount' => $selectedCategory ? $this->linkedCount($selectedCategory) : 0,
            'linkedLabel' => __($this->definition('linked_label')),
            'showCategoryUrl' => $this->showCategoryUrl(),
            'rootCreateUrl' => $this->categoryCreateUrl(),
            'moduleItemsUrl' => $this->moduleItemsUrl(),
        ]);
    }

    /**
     * @return EloquentCollection<int, Model>
     */
    private function categories(): EloquentCollection
    {
        return $this->modelClass()::query()
            ->withCount($this->relationName())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  EloquentCollection<int, Model>  $categories
     */
    private function selectedCategory(EloquentCollection $categories): ?Model
    {
        if (! $this->selectedCategoryId) {
            return null;
        }

        return $categories->firstWhere('id', $this->selectedCategoryId);
    }

    /**
     * @return Collection<int, Model>
     */
    private function linkedItems(Model $category): Collection
    {
        return $category->{$this->relationName()}()
            ->limit(50)
            ->get()
            ->map(fn (Model $item): Model => $item);
    }

    private function linkedCount(Model $category): int
    {
        return (int) ($category->{Str::snake($this->relationName()).'_count'} ?? 0);
    }

    public function linkedItemTitle(Model $item): string
    {
        return (string) data_get($item, $this->definition('item_title'), __('Naamloos item'));
    }

    public function categoryUrl(Model $category): ?string
    {
        $attributes = $category->getAttributes();
        $path = (string) (($attributes['custom_url'] ?? null) ?: ($attributes['slug'] ?? ''));

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url('/'.ltrim($path, '/'));
    }

    public function categoryCreateUrl(?int $parentId = null): string
    {
        $params = array_filter(['parent' => $parentId]);

        return route($this->categoryRouteName($this->usesCmsRoutes() ? 'edit' : 'create'), $params);
    }

    public function categoryEditUrl(Model $category): string
    {
        return route($this->categoryRouteName('edit'), ['id' => $category->getKey()]);
    }

    public function categoryDeleteUrl(Model $category): string
    {
        return route($this->categoryRouteName('destroy'), $category);
    }

    public function moduleItemsUrl(?Model $category = null): string
    {
        $routeName = ($this->usesCmsRoutes() ? 'cms.' : 'admin.').$this->definition('item_route');
        $params = $category ? ['categoryId' => $category->getKey()] : [];

        return Route::has($routeName) ? route($routeName, $params) : '#';
    }

    public function statusClass(Model $category): string
    {
        return in_array((string) $category->status, ['1', 'active', 'online', 'published'], true)
            ? 'active-item'
            : 'inactive-item';
    }

    public function statusLabel(Model $category): string
    {
        return match ((string) $category->status) {
            '1', 'active', 'online', 'published' => __('Actief'),
            '0', 'inactive', 'offline', 'draft' => __('Inactief'),
            default => __(Str::headline((string) $category->status)),
        };
    }

    /**
     * @return class-string<Model>
     */
    private function modelClass(): string
    {
        return $this->definition('model');
    }

    private function relationName(): string
    {
        return $this->definition('relation');
    }

    private function categoryRouteName(string $action): string
    {
        return ($this->usesCmsRoutes() ? 'cms.' : 'admin.').$this->definition('category_route').$action;
    }

    private function usesCmsRoutes(): bool
    {
        return request()->routeIs('cms.*');
    }

    private function definition(string $key): string
    {
        return (string) config("cms_categories.{$this->module}.{$key}");
    }

    private function showCategoryUrl(): bool
    {
        return (bool) config("cms_categories.{$this->module}.show_url", true);
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>|null
     */
    private function sortedIdsAfterMove(array $ids, int $draggedId, int $targetId, string $position): ?array
    {
        $from = array_search($draggedId, $ids, true);

        if ($from === false || ! in_array($targetId, $ids, true)) {
            return null;
        }

        array_splice($ids, $from, 1);
        $to = array_search($targetId, $ids, true);

        if ($to === false) {
            return null;
        }

        if ($position === 'after') {
            $to++;
        }

        array_splice($ids, $to, 0, [$draggedId]);

        return array_values($ids);
    }
}
