<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\SearchRequest;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Domain;
use App\Models\Cms\Page;
use App\Support\Domains\DomainResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    public function __invoke(SearchRequest $request): View
    {
        $query = trim((string) $request->validated('q', ''));
        $domain = app()->bound(DomainResolver::CONTAINER_KEY)
            ? app(DomainResolver::CONTAINER_KEY)
            : null;

        return view('frontend.search.index', [
            'page' => [
                'title' => __('Search'),
                'meta_description' => __('Search the website.'),
            ],
            'query' => $query,
            'pageResults' => $query === '' ? collect() : $this->pages($query, $domain),
            'contentResults' => $query === '' ? collect() : $this->contentItems($query, $domain),
        ]);
    }

    /**
     * @return Collection<int, Page>
     */
    private function pages(string $query, mixed $domain): Collection
    {
        $pages = Page::query()
            ->published()
            ->where(function (Builder $builder) use ($query): void {
                $builder->where('title', 'like', "%{$query}%")
                    ->orWhere('navigation_label', 'like', "%{$query}%")
                    ->orWhere('excerpt', 'like', "%{$query}%")
                    ->orWhere('body', 'like', "%{$query}%");
            })
            ->when($domain instanceof Domain, function (Builder $builder) use ($domain): void {
                $builder->where(function (Builder $builder) use ($domain): void {
                    $builder->where('domain_id', $domain->id)
                        ->orWhereNull('domain_id');
                });
            })
            ->ordered()
            ->limit(20)
            ->get();

        return $this->preferDomainRecords($pages, $domain);
    }

    /**
     * @return Collection<int, ContentItem>
     */
    private function contentItems(string $query, mixed $domain): Collection
    {
        $items = ContentItem::query()
            ->online()
            ->where(function (Builder $builder) use ($query): void {
                $builder->where('title', 'like', "%{$query}%")
                    ->orWhere('subtitle', 'like', "%{$query}%")
                    ->orWhere('structured_blocks', 'like', "%{$query}%");
            })
            ->where(function (Builder $builder): void {
                $builder->whereNull('locale')
                    ->orWhere('locale', app()->getLocale());
            })
            ->when($domain instanceof Domain, function (Builder $builder) use ($domain): void {
                $builder->where(function (Builder $builder) use ($domain): void {
                    $builder->where('domain_id', $domain->id)
                        ->orWhereNull('domain_id');
                });
            })
            ->orderBy('title')
            ->limit(20)
            ->get();

        return $this->preferDomainRecords($items, $domain);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Collection<int, TModel>  $records
     * @return Collection<int, TModel>
     */
    private function preferDomainRecords(Collection $records, mixed $domain): Collection
    {
        if (! $domain instanceof Domain) {
            return $records->values();
        }

        return $records
            ->sortByDesc(fn ($record): bool => (int) $record->getAttribute('domain_id') === $domain->id)
            ->unique(fn ($record): string => (string) $record->getAttribute('slug'))
            ->values();
    }
}
