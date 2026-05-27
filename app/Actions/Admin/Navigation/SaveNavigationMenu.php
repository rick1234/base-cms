<?php

namespace App\Actions\Admin\Navigation;

use App\Models\Cms\NavigationMenu;
use App\Models\Cms\NavigationMenuItem;
use App\Models\User;
use App\Support\Navigation\NavigationLinkRegistry;
use Illuminate\Support\Facades\DB;

class SaveNavigationMenu
{
    public function __construct(private readonly NavigationLinkRegistry $links) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(array $data, array $items, ?User $user, ?NavigationMenu $menu = null): NavigationMenu
    {
        return DB::transaction(function () use ($data, $items, $user, $menu): NavigationMenu {
            $menu ??= new NavigationMenu;
            $menu->fill([
                'name' => $data['name'],
                'handle' => $data['handle'],
                'domain_id' => $data['domain_id'] ?? null,
                'locale' => $data['locale'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'updated_by' => $user?->id,
            ]);

            if (! $menu->exists) {
                $menu->created_by = $user?->id;
            }

            $menu->save();

            NavigationMenuItem::query()
                ->where('navigation_menu_id', $menu->id)
                ->delete();

            $this->createItems($menu, $items, null, $user);

            return $menu->refresh();
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function createItems(NavigationMenu $menu, array $items, ?NavigationMenuItem $parent, ?User $user): void
    {
        foreach (array_values($items) as $index => $itemData) {
            $linkType = (string) ($itemData['link_type'] ?? 'custom');
            $customUrl = $linkType === 'custom' ? trim((string) ($itemData['custom_url'] ?? '')) : null;
            $opensNewTab = $customUrl !== null && $this->links->isExternalUrl($customUrl);
            $isCategory = $this->links->isCategoryType($linkType);

            $item = NavigationMenuItem::query()->create([
                'navigation_menu_id' => $menu->id,
                'parent_id' => $parent?->id,
                'title' => (string) ($itemData['title'] ?? ''),
                'link_type' => $linkType,
                'link_id' => $linkType === 'custom' ? null : (int) ($itemData['link_id'] ?? 0),
                'custom_url' => $customUrl,
                'opens_new_tab' => $opensNewTab,
                'expand_children' => $isCategory && (bool) ($itemData['expand_children'] ?? false),
                'is_active' => (bool) ($itemData['is_active'] ?? true),
                'sort_order' => $index + 1,
                'metadata' => [
                    'target_label' => $itemData['target_label'] ?? $itemData['label'] ?? null,
                    'target_type_label' => $itemData['target_type_label'] ?? null,
                ],
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);

            if (! empty($itemData['children']) && is_array($itemData['children'])) {
                $this->createItems($menu, $itemData['children'], $item, $user);
            }
        }
    }
}
