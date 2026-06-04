<?php

namespace App\Support\Navigation;

use App\Models\Cms\Domain;
use App\Models\Cms\NavigationMenu;
use App\Models\Cms\NavigationMenuItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FrontendNavigationBuilder
{
    public function __construct(private readonly NavigationLinkRegistry $links) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function tree(string $handle = 'primary', ?Domain $domain = null, ?string $locale = null): Collection
    {
        $menu = $this->menu($handle, $domain, $this->normalizeLocale($locale));

        if (! $menu instanceof NavigationMenu) {
            return collect();
        }

        $items = $menu->items()
            ->active()
            ->get()
            ->groupBy(fn (NavigationMenuItem $item): int => $item->parent_id ?: 0);

        return $this->childrenFor(0, $items);
    }

    private function menu(string $handle, ?Domain $domain, ?string $locale): ?NavigationMenu
    {
        if (! $this->hasNavigationTables()) {
            return null;
        }

        try {
            $scopes = [];

            if ($domain instanceof Domain) {
                if ($locale !== null) {
                    $scopes[] = ['domain_id' => $domain->id, 'locale' => $locale];
                }

                $scopes[] = ['domain_id' => $domain->id, 'locale' => null];
            }

            if ($locale !== null) {
                $scopes[] = ['domain_id' => null, 'locale' => $locale];
            }

            $scopes[] = ['domain_id' => null, 'locale' => null];

            foreach ($scopes as $scope) {
                $query = NavigationMenu::query()
                    ->active()
                    ->where('handle', $handle)
                    ->ordered();

                $scope['domain_id'] === null
                    ? $query->whereNull('domain_id')
                    : $query->where('domain_id', $scope['domain_id']);

                $scope['locale'] === null
                    ? $query->whereNull('locale')
                    : $query->where('locale', $scope['locale']);

                $menu = $query->first();

                if ($menu instanceof NavigationMenu) {
                    return $menu;
                }
            }

            return null;
        } catch (QueryException) {
            return null;
        }
    }

    /**
     * @param  Collection<int, Collection<int, NavigationMenuItem>>  $itemsByParent
     * @return Collection<int, array<string, mixed>>
     */
    private function childrenFor(int $parentId, Collection $itemsByParent): Collection
    {
        return $itemsByParent
            ->get($parentId, collect())
            ->map(function (NavigationMenuItem $item) use ($itemsByParent): ?array {
                $url = $this->links->urlFor($item->link_type, $item->link_id, $item->custom_url);

                if (! $url) {
                    return null;
                }

                $children = $this->childrenFor($item->id, $itemsByParent);

                if ($item->expand_children) {
                    $children = $children->concat($this->links->expandedCategoryChildren($item->link_type, $item->link_id));
                }

                return [
                    'title' => $item->title,
                    'url' => $url,
                    'target_blank' => $item->opens_new_tab || $this->links->isExternalUrl($url),
                    'external' => $this->links->isExternalUrl($url),
                    'children' => $children->values()->all(),
                ];
            })
            ->filter()
            ->values();
    }

    private function hasNavigationTables(): bool
    {
        try {
            return Schema::hasTable('navigation_menus')
                && Schema::hasTable('navigation_menu_items');
        } catch (QueryException) {
            return false;
        }
    }

    private function normalizeLocale(?string $locale): ?string
    {
        $locale = strtolower(trim(str_replace('_', '-', (string) $locale)));

        return $locale !== '' ? $locale : null;
    }
}
