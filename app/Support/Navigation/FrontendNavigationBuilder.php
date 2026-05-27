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
    public function tree(string $handle = 'primary', ?Domain $domain = null): Collection
    {
        $menu = $this->menu($handle, $domain);

        if (! $menu instanceof NavigationMenu) {
            return collect();
        }

        $items = $menu->items()
            ->active()
            ->get()
            ->groupBy(fn (NavigationMenuItem $item): int => $item->parent_id ?: 0);

        return $this->childrenFor(0, $items);
    }

    private function menu(string $handle, ?Domain $domain): ?NavigationMenu
    {
        if (! $this->hasNavigationTables()) {
            return null;
        }

        try {
            $baseQuery = NavigationMenu::query()
                ->active()
                ->where('handle', $handle)
                ->whereNull('locale')
                ->ordered();

            if ($domain instanceof Domain) {
                $domainMenu = (clone $baseQuery)
                    ->where('domain_id', $domain->id)
                    ->first();

                if ($domainMenu instanceof NavigationMenu) {
                    return $domainMenu;
                }
            }

            return (clone $baseQuery)->whereNull('domain_id')->first();
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
}
