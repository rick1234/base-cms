<?php

namespace App\Http\Controllers\Admin\Navigation;

use App\Actions\Admin\Navigation\SaveNavigationMenu;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Navigation\NavigationLinkSearchRequest;
use App\Http\Requests\Admin\Navigation\NavigationMenuRequest;
use App\Models\Cms\CmsLanguage;
use App\Models\Cms\Domain;
use App\Models\Cms\NavigationMenu;
use App\Models\Cms\NavigationMenuItem;
use App\Support\Navigation\NavigationLinkRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class NavigationMenuController extends Controller
{
    public function __construct(private readonly NavigationLinkRegistry $links) {}

    public function index(): View
    {
        return view('admin.navigation.index', [
            'menus' => NavigationMenu::query()
                ->with('domain')
                ->withCount('items')
                ->ordered()
                ->get(),
            'pageName' => __('Navigation'),
        ]);
    }

    public function create(): View
    {
        return $this->form(new NavigationMenu([
            'handle' => 'primary',
            'name' => __('Primary navigation'),
            'locale' => $this->defaultLocale(),
            'is_active' => true,
        ]));
    }

    public function store(NavigationMenuRequest $request, SaveNavigationMenu $save): RedirectResponse
    {
        $menu = $save->handle($request->validated(), $request->navigationItems(), $request->user());

        flash(__('Navigation menu created.'))->success();

        return redirect()->route('admin.navigation.edit', $menu);
    }

    public function edit(NavigationMenu $navigationMenu): View
    {
        return $this->form($navigationMenu);
    }

    public function update(NavigationMenuRequest $request, NavigationMenu $navigationMenu, SaveNavigationMenu $save): RedirectResponse
    {
        $menu = $save->handle($request->validated(), $request->navigationItems(), $request->user(), $navigationMenu);

        flash(__('Navigation menu saved.'))->success();

        return redirect()->route('admin.navigation.edit', $menu);
    }

    public function destroy(NavigationMenu $navigationMenu): RedirectResponse
    {
        $navigationMenu->delete();

        flash(__('Navigation menu deleted.'))->success();

        return redirect()->route('admin.navigation.index');
    }

    public function linkTypes(Request $request): JsonResponse
    {
        return response()->json([
            'types' => $this->links->typeOptions(
                $request->string('locale')->toString() ?: null,
                $request->boolean('all_languages'),
            ),
        ]);
    }

    public function linkOptions(NavigationLinkSearchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return response()->json([
            'results' => $this->links->searchOptions(
                (string) $validated['type'],
                $validated['q'] ?? null,
                $validated['locale'] ?? null,
                (bool) ($validated['all_languages'] ?? false),
            ),
        ]);
    }

    private function form(NavigationMenu $menu): View
    {
        $menu->load('items');

        return view('admin.navigation.edit', [
            'menu' => $menu,
            'domains' => Domain::query()->ordered()->get(),
            'languages' => $this->languages(),
            'defaultLocale' => $this->defaultLocale(),
            'linkTypes' => $this->links->typeOptions($menu->locale ?: $this->defaultLocale()),
            'itemsPayload' => $this->itemsPayload($menu),
            'pageName' => __('Navigation'),
            'backUrl' => route('admin.navigation.index'),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function itemsPayload(NavigationMenu $menu): array
    {
        if (! $menu->exists) {
            return [];
        }

        $items = $menu->items
            ->sortBy([['sort_order', 'asc'], ['title', 'asc']])
            ->groupBy(fn (NavigationMenuItem $item): int => $item->parent_id ?: 0);

        return $this->serializeChildren(0, $items)->all();
    }

    /**
     * @return Collection<int, CmsLanguage>
     */
    private function languages(): Collection
    {
        if (! Schema::hasTable('languages')) {
            return collect();
        }

        return CmsLanguage::query()
            ->enabled()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function defaultLocale(): string
    {
        $default = $this->languages()->firstWhere('is_default', true)?->code
            ?: config('app.locale', 'nl');

        return strtolower((string) $default);
    }

    /**
     * @param  Collection<int, Collection<int, NavigationMenuItem>>  $itemsByParent
     * @return Collection<int, array<string, mixed>>
     */
    private function serializeChildren(int $parentId, Collection $itemsByParent): Collection
    {
        return $itemsByParent
            ->get($parentId, collect())
            ->map(function (NavigationMenuItem $item) use ($itemsByParent): array {
                $target = $this->links->optionFor($item->link_type, $item->link_id);

                return [
                    'title' => $item->title,
                    'link_type' => $item->link_type,
                    'link_id' => $item->link_id,
                    'custom_url' => $item->custom_url,
                    'is_active' => $item->is_active,
                    'expand_children' => $item->expand_children,
                    'target_label' => $target['label'] ?? data_get($item->metadata, 'target_label'),
                    'target_type_label' => $target['type_label'] ?? data_get($item->metadata, 'target_type_label'),
                    'source_edit_url' => $target['source_edit_url'] ?? null,
                    'is_category' => $this->links->isCategoryType($item->link_type),
                    'children' => $this->serializeChildren($item->id, $itemsByParent)->all(),
                ];
            })
            ->values();
    }
}
