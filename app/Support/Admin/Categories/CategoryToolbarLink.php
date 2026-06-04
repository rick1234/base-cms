<?php

namespace App\Support\Admin\Categories;

use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

class CategoryToolbarLink
{
    /**
     * @return array{label: string, url: string}|null
     */
    public function forRequest(Request $request): ?array
    {
        $route = $request->route();

        if (! $route instanceof RoutingRoute) {
            return null;
        }

        $screenKey = $this->screenKey($route);
        $screen = is_string($screenKey) ? config("cms_modules.screens.{$screenKey}") : null;
        $parentScreenKey = is_array($screen) ? data_get($screen, 'parent_screen') : null;
        $parentScreen = is_string($parentScreenKey) ? config("cms_modules.screens.{$parentScreenKey}") : null;

        if (! is_array($parentScreen)) {
            return null;
        }

        $routeName = (string) $route->getName();
        $usesCmsRoutes = str_starts_with($routeName, 'cms.');
        $url = $this->itemRouteUrl($routeName, $usesCmsRoutes)
            ?? $this->screenUrl($parentScreen, $usesCmsRoutes);

        if ($url === null) {
            return null;
        }

        return [
            'label' => __((string) data_get($parentScreen, 'pages.index.name', $parentScreen['name'])),
            'url' => $url,
        ];
    }

    private function screenKey(RoutingRoute $route): ?string
    {
        $screenHandles = Arr::flatten(Arr::wrap($route->getAction('admin_screen')));
        $screenHandle = collect($screenHandles)
            ->filter(fn (mixed $handle): bool => is_string($handle) && $handle !== '')
            ->last();

        return is_string($screenHandle) ? $screenHandle : null;
    }

    private function itemRouteUrl(string $routeName, bool $usesCmsRoutes): ?string
    {
        $prefix = $usesCmsRoutes ? 'cms.' : 'admin.';

        foreach (config('cms_categories', []) as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $categoryRoute = data_get($definition, 'category_route');
            $itemRoute = data_get($definition, 'item_route');

            if (! is_string($categoryRoute) || ! is_string($itemRoute)) {
                continue;
            }

            $categoryRouteName = $prefix.$categoryRoute;
            $itemRouteName = $prefix.$itemRoute;

            if (str_starts_with($routeName, $categoryRouteName) && Route::has($itemRouteName)) {
                return route($itemRouteName);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $screen
     */
    private function screenUrl(array $screen, bool $usesCmsRoutes): ?string
    {
        if (! $usesCmsRoutes && isset($screen['admin_route']) && is_string($screen['admin_route']) && Route::has($screen['admin_route'])) {
            return route($screen['admin_route']);
        }

        $routeName = $usesCmsRoutes ? 'cms.modules.index' : 'admin.modules.show';

        return Route::has($routeName)
            ? route($routeName, trim(str_replace('cms/', '', (string) $screen['legacy_path']), '/'))
            : null;
    }
}
