<?php

namespace App\Livewire\Admin\Templates;

use App\Models\Cms\WebsiteTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class TemplateOverview extends Component
{
    use WithPagination;

    public string $filterId = '';

    public string $filterName = '';

    public string $filterHandle = '';

    public string $filterStatus = '';

    public string $draftId = '';

    public string $draftName = '';

    public string $draftHandle = '';

    public string $draftStatus = '';

    public string $sort = 'sort-order';

    public string $direction = 'asc';

    public int $perPage = 25;

    /**
     * @var array<string, mixed>
     */
    protected array $queryString = [
        'filterId' => ['as' => 'id', 'except' => ''],
        'filterName' => ['as' => 'name', 'except' => ''],
        'filterHandle' => ['as' => 'handle', 'except' => ''],
        'filterStatus' => ['as' => 'status', 'except' => ''],
            'sort' => ['except' => 'sort-order'],
        'direction' => ['as' => 'sorttype', 'except' => 'asc'],
    ];

    public function mount(): void
    {
        $this->direction = $this->normalizeDirection($this->direction ?: request()->string('sorttype')->toString());
        $this->sort = $this->normalizeSort($this->sort ?: request()->string('sort')->toString());

        $this->syncDraftsFromFilters();
    }

    public function applyFilters(): void
    {
        $this->filterId = trim($this->draftId);
        $this->filterName = trim($this->draftName);
        $this->filterHandle = trim($this->draftHandle);
        $this->filterStatus = trim($this->draftStatus);

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filterId = '';
        $this->filterName = '';
        $this->filterHandle = '';
        $this->filterStatus = '';

        $this->syncDraftsFromFilters();
        $this->resetPage();
    }

    public function sortBy(string $column, string $direction = 'asc'): void
    {
        $this->sort = $this->normalizeSort($column);
        $this->direction = $this->normalizeDirection($direction);

        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin.templates.template-overview', [
            'templates' => $this->templates(),
        ]);
    }

    private function syncDraftsFromFilters(): void
    {
        $this->draftId = $this->filterId;
        $this->draftName = $this->filterName;
        $this->draftHandle = $this->filterHandle;
        $this->draftStatus = $this->filterStatus;
    }

    /**
     * @return LengthAwarePaginator<int, WebsiteTemplate>
     */
    private function templates(): LengthAwarePaginator
    {
        $query = WebsiteTemplate::query()->withCount('domains');

        if ($this->filterId !== '') {
            $query->whereKey((int) $this->filterId);
        }

        if ($this->filterName !== '') {
            $query->where('name', 'like', '%'.$this->filterName.'%');
        }

        if ($this->filterHandle !== '') {
            $query->where('handle', 'like', '%'.$this->filterHandle.'%');
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus === 'active');
        }

        return $query
            ->orderBy($this->sortColumn(), $this->direction)
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    private function sortColumn(): string
    {
        return match ($this->sort) {
            'name' => 'name',
            'handle' => 'handle',
            'id' => 'id',
            'status' => 'is_active',
            default => 'sort_order',
        };
    }

    private function normalizeSort(string $sort): string
    {
        return match ($sort) {
            'name', 'naam' => 'name',
            'handle', 'technical-name' => 'handle',
            'id' => 'id',
            'status' => 'status',
            'sort_index', 'sort_order', 'sort-order' => 'sort-order',
            default => 'sort-order',
        };
    }

    private function normalizeDirection(string $direction): string
    {
        return strtolower($direction) === 'desc' ? 'desc' : 'asc';
    }
}
