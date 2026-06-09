<?php

namespace App\Livewire\Admin\Domains;

use App\Models\Cms\Domain;
use App\Models\Cms\WebsiteTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class DomainOverview extends Component
{
    use WithPagination;

    public string $filterId = '';

    public string $filterHost = '';

    public string $filterName = '';

    public string $filterTemplateId = '';

    public string $filterLocale = '';

    public string $filterStatus = '';

    public string $draftId = '';

    public string $draftHost = '';

    public string $draftName = '';

    public string $draftTemplateId = '';

    public string $draftLocale = '';

    public string $draftStatus = '';

    public string $sort = 'sort-order';

    public string $direction = 'asc';

    public int $perPage = 25;

    /**
     * @var array<string, mixed>
     */
    protected array $queryString = [
        'filterId' => ['as' => 'id', 'except' => ''],
        'filterHost' => ['as' => 'host', 'except' => ''],
        'filterName' => ['as' => 'name', 'except' => ''],
        'filterTemplateId' => ['as' => 'template', 'except' => ''],
        'filterLocale' => ['as' => 'locale', 'except' => ''],
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
        $this->filterHost = trim($this->draftHost);
        $this->filterName = trim($this->draftName);
        $this->filterTemplateId = trim($this->draftTemplateId);
        $this->filterLocale = trim($this->draftLocale);
        $this->filterStatus = trim($this->draftStatus);

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filterId = '';
        $this->filterHost = '';
        $this->filterName = '';
        $this->filterTemplateId = '';
        $this->filterLocale = '';
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
        return view('livewire.admin.domains.domain-overview', [
            'domains' => $this->domains(),
            'templates' => $this->templates(),
            'localeOptions' => $this->localeOptions(),
        ]);
    }

    public function localeLabel(string $locale): string
    {
        return match ($locale) {
            'nl' => __('Dutch'),
            'en' => __('English'),
            'de' => __('German'),
            'fr' => __('French'),
            default => strtoupper($locale),
        };
    }

    private function syncDraftsFromFilters(): void
    {
        $this->draftId = $this->filterId;
        $this->draftHost = $this->filterHost;
        $this->draftName = $this->filterName;
        $this->draftTemplateId = $this->filterTemplateId;
        $this->draftLocale = $this->filterLocale;
        $this->draftStatus = $this->filterStatus;
    }

    /**
     * @return LengthAwarePaginator<int, Domain>
     */
    private function domains(): LengthAwarePaginator
    {
        $query = Domain::query()->with(['template', 'aliases']);

        if ($this->filterId !== '') {
            $query->whereKey((int) $this->filterId);
        }

        if ($this->filterHost !== '') {
            $query->where(function ($query): void {
                $query->where('host', 'like', '%'.$this->filterHost.'%')
                    ->orWhereHas('aliases', fn ($aliasQuery) => $aliasQuery->where('host', 'like', '%'.$this->filterHost.'%'));
            });
        }

        if ($this->filterName !== '') {
            $query->where('name', 'like', '%'.$this->filterName.'%');
        }

        if ($this->filterTemplateId !== '') {
            $query->where('website_template_id', (int) $this->filterTemplateId);
        }

        if ($this->filterLocale !== '') {
            $query->where('default_locale', $this->filterLocale);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus === 'active');
        }

        return $query
            ->orderBy($this->sortColumn(), $this->direction)
            ->orderBy('host')
            ->paginate($this->perPage);
    }

    /**
     * @return Collection<int, WebsiteTemplate>
     */
    private function templates(): Collection
    {
        return WebsiteTemplate::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<string>
     */
    private function localeOptions(): array
    {
        return Domain::query()
            ->select('default_locale')
            ->whereNotNull('default_locale')
            ->distinct()
            ->orderBy('default_locale')
            ->pluck('default_locale')
            ->map(fn (string $locale): string => $locale)
            ->filter()
            ->values()
            ->all();
    }

    private function sortColumn(): string
    {
        return match ($this->sort) {
            'host' => 'host',
            'name' => 'name',
            'locale' => 'default_locale',
            'status' => 'is_active',
            'id' => 'id',
            default => 'sort_order',
        };
    }

    private function normalizeSort(string $sort): string
    {
        return match ($sort) {
            'host', 'domain' => 'host',
            'name', 'naam' => 'name',
            'locale', 'taalcode' => 'locale',
            'status' => 'status',
            'id' => 'id',
            'sort_index', 'sort_order', 'sort-order' => 'sort-order',
            default => 'sort-order',
        };
    }

    private function normalizeDirection(string $direction): string
    {
        return strtolower($direction) === 'desc' ? 'desc' : 'asc';
    }
}
