<?php

namespace App\Support\Admin\Breadcrumbs;

use App\Support\Cms\CmsModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AdminBreadcrumbBuilder
{
    /**
     * @var array<string, string>
     */
    private const ROUTE_PREFIX_SCREENS = [
        'admin.banners.categories.' => 'banner_categories',
        'admin.banners.' => 'banners',
        'admin.catalog.categories.' => 'catalog_categories',
        'admin.catalog.brands.' => 'catalog_brands',
        'admin.catalog.promotions.' => 'catalog_promotions',
        'admin.catalog.reviews.' => 'catalog_reviews',
        'admin.catalog.coupons.' => 'catalog_coupons',
        'admin.catalog.' => 'catalog_products',
        'admin.pages.' => 'content_items',
        'admin.content.categories.' => 'content_categories',
        'admin.content.' => 'content_items',
        'admin.downloads.categories.' => 'download_categories',
        'admin.downloads.' => 'downloads',
        'admin.events.categories.' => 'event_categories',
        'admin.events.' => 'events',
        'admin.faq.categories.' => 'faq_categories',
        'admin.faq.' => 'faq_items',
        'admin.forms.categories.' => 'form_categories',
        'admin.forms.' => 'forms',
        'admin.locations.categories.' => 'location_categories',
        'admin.locations.' => 'locations',
        'admin.users.categories.' => 'user_categories',
        'admin.users.' => 'users',
        'admin.vacancies.categories.' => 'vacancy_categories',
        'admin.vacancies.' => 'vacancies',
        'admin.redirects.' => 'redirects',
        'admin.roles.' => 'roles',
        'admin.domains.' => 'domains',
        'admin.templates.' => 'website_templates',
        'admin.navigation.' => 'navigation',
        'admin.countries.languages.' => 'countries',
        'admin.countries.' => 'countries',
        'admin.translations.' => 'translations',
    ];

    /**
     * @var array<string, string>
     */
    private const ROUTE_SUFFIX_PAGES = [
        'bulk' => 'bulkUploader',
        'combinations' => 'editCombinaties',
        'images' => 'editAfbeeldingen',
        'options' => 'editOptions',
        'stock' => 'editVoorraad',
        'submissions' => 'editMessages',
        'sync' => 'sync',
        'translations' => 'editVertalingen',
        'videos' => 'editVideo',
        'languages.index' => 'talen',
        'languages.edit' => 'talen',
        'languages.create' => 'talen',
    ];

    public function __construct(private readonly CmsModuleRegistry $modules) {}

    /**
     * @return Collection<int, array{label: string, url: string|null}>
     */
    public function forRequest(Request $request): Collection
    {
        $route = $request->route();

        if (! $route instanceof RoutingRoute || ! $this->isAdminRoute($route)) {
            return collect();
        }

        $routeName = (string) $route->getName();

        if ($this->isUtilityRoute($routeName)) {
            return collect();
        }

        if (in_array($routeName, ['admin.dashboard', 'cms.dashboard'], true)) {
            return collect();
        }

        $breadcrumbs = collect([
            $this->item(__('Home'), $this->homeUrl($routeName)),
        ]);

        if (in_array($routeName, ['admin.modules.index', 'cms.index'], true)) {
            return $breadcrumbs->push($this->item(__('Admin Modules')));
        }

        $screen = $this->screenForRoute($routeName, $route);

        if (! $screen) {
            return $breadcrumbs;
        }

        $parentScreen = $this->parentScreen($screen);
        $groupScreen = $parentScreen ?? $screen;
        $group = (string) ($groupScreen['group'] ?? '');
        $groups = config('cms_modules.groups', []);

        if ($group !== '' && isset($groups[$group])) {
            $breadcrumbs->push($this->item(__($groups[$group])));
        }

        $screenLabel = __($this->screenBreadcrumbLabel($screen, $parentScreen !== null));
        $screenUrl = $this->screenUrl($screen, str_starts_with($routeName, 'cms.'));
        $pageLabel = $this->pageLabel($screen, $routeName, $route);

        if ($parentScreen) {
            $breadcrumbs->push($this->item(
                __($this->screenBreadcrumbLabel($parentScreen, true)),
                $this->screenUrl($parentScreen, str_starts_with($routeName, 'cms.')),
            ));
        }

        if ($pageLabel === null) {
            return $breadcrumbs->push($this->item($screenLabel));
        }

        $breadcrumbs->push($this->item($screenLabel, $screenUrl));
        $breadcrumbs->push($this->item($pageLabel));

        return $breadcrumbs;
    }

    private function isAdminRoute(RoutingRoute $route): bool
    {
        $name = (string) $route->getName();

        return str_starts_with($name, 'admin.') || str_starts_with($name, 'cms.');
    }

    private function isUtilityRoute(string $routeName): bool
    {
        return str_contains($routeName, 'login')
            || str_contains($routeName, 'logout')
            || str_contains($routeName, '.store')
            || str_contains($routeName, '.save')
            || str_contains($routeName, '.update')
            || str_contains($routeName, '.destroy')
            || str_contains($routeName, '.delete')
            || str_contains($routeName, '.upload')
            || str_contains($routeName, '.duplicate')
            || str_contains($routeName, '.generate')
            || str_contains($routeName, '.bulk.')
            || str_contains($routeName, '.ajax.')
            || str_contains($routeName, '.image.')
            || str_contains($routeName, '.file.')
            || str_contains($routeName, '.link.');
    }

    private function homeUrl(string $routeName): string
    {
        $homeRoute = str_starts_with($routeName, 'cms.') ? 'cms.index' : 'admin.dashboard';

        return Route::has($homeRoute) ? route($homeRoute) : url('/admin');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function screenForRoute(string $routeName, RoutingRoute $route): ?array
    {
        if ($screen = $this->screenFromRouteAction($route)) {
            return $screen;
        }

        if (str_starts_with($routeName, 'admin.modules.') || str_starts_with($routeName, 'cms.modules.')) {
            return $this->moduleScreenForRoute($route);
        }

        $navigationRouteName = str_starts_with($routeName, 'cms.')
            ? 'admin.'.Str::after($routeName, 'cms.')
            : $routeName;

        foreach (self::ROUTE_PREFIX_SCREENS as $prefix => $screenKey) {
            if (str_starts_with($navigationRouteName, $prefix)) {
                return $this->screen($screenKey);
            }
        }

        return $this->screenForPath($route);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function screenFromRouteAction(RoutingRoute $route): ?array
    {
        $screenHandles = Arr::flatten(Arr::wrap($route->getAction('admin_screen')));
        $screenHandle = collect($screenHandles)
            ->filter(fn (mixed $handle): bool => is_string($handle) && $handle !== '')
            ->last();

        return is_string($screenHandle) ? $this->screen($screenHandle) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function moduleScreenForRoute(RoutingRoute $route): ?array
    {
        $modulePath = (string) ($route->parameter('modulePath') ?? '');

        if ($modulePath === '') {
            return null;
        }

        $page = $route->parameter('page');
        $handleOrFolder = is_string($page) && $page !== ''
            ? trim($modulePath.'/'.Str::beforeLast($page, '.php'), '/')
            : $modulePath;

        return $this->modules->findOptional($handleOrFolder)
            ?? $this->modules->findOptional($modulePath);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function screen(string $screenKey): ?array
    {
        $screen = config("cms_modules.screens.{$screenKey}");

        if (! is_array($screen)) {
            return null;
        }

        return [
            ...$screen,
            'handle' => $screenKey,
            'folder' => $this->folderSegment($screen),
        ];
    }

    /**
     * @param  array<string, mixed>  $screen
     * @return array<string, mixed>|null
     */
    private function parentScreen(array $screen): ?array
    {
        $parentScreenKey = data_get($screen, 'parent_screen');

        return is_string($parentScreenKey) && $parentScreenKey !== ''
            ? $this->screen($parentScreenKey)
            : null;
    }

    /**
     * @param  array<string, mixed>  $screen
     */
    private function screenBreadcrumbLabel(array $screen, bool $preferOverviewLabel = false): string
    {
        if ($preferOverviewLabel) {
            return (string) data_get($screen, 'pages.index.name', $screen['name']);
        }

        return (string) $screen['name'];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function screenForPath(RoutingRoute $route): ?array
    {
        $path = trim(Str::after($route->uri(), 'admin/'), '/');
        $path = trim(Str::after($path, 'cms/'), '/');

        return collect(config('cms_modules.screens', []))
            ->map(fn (array $screen, string $handle): array => [
                ...$screen,
                'handle' => $handle,
                'folder' => $this->folderSegment($screen),
            ])
            ->sortByDesc(fn (array $screen): int => strlen((string) $screen['folder']))
            ->first(fn (array $screen): bool => str_starts_with($path, (string) $screen['folder']));
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
            ? route($routeName, $screen['folder'])
            : null;
    }

    /**
     * @param  array<string, mixed>  $screen
     */
    private function pageLabel(array $screen, string $routeName, RoutingRoute $route): ?string
    {
        $suffix = $this->routeSuffix($routeName);
        $action = Str::afterLast($routeName, '.');
        $pageKey = $this->pageKeyForSuffix($suffix);
        $pages = (array) ($screen['pages'] ?? []);

        if ($action === 'index' || $action === 'legacy-index') {
            return null;
        }

        if ($action === 'create') {
            return __('Toevoegen');
        }

        if (($action === 'edit' || str_contains($suffix, 'legacy-edit')) && isset($pages['edit'])) {
            return __((string) data_get($pages, 'edit.name', 'Bewerken'));
        }

        if ($pageKey && isset($pages[$pageKey])) {
            return __((string) data_get($pages, "{$pageKey}.name", $screen['name']));
        }

        $page = $route->parameter('page');

        if (is_string($page)) {
            $normalizedPage = Str::beforeLast($page, '.php');

            if (isset($pages[$normalizedPage])) {
                return __((string) data_get($pages, "{$normalizedPage}.name", $screen['name']));
            }
        }

        $tab = $route->parameter('tab');

        if (is_string($tab) && isset($pages[$tab])) {
            return __((string) data_get($pages, "{$tab}.name", $screen['name']));
        }

        return null;
    }

    private function routeSuffix(string $routeName): string
    {
        return Str::after($routeName, str_starts_with($routeName, 'cms.') ? 'cms.' : 'admin.');
    }

    private function pageKeyForSuffix(string $suffix): ?string
    {
        foreach (self::ROUTE_SUFFIX_PAGES as $routeSuffix => $pageKey) {
            if (str_ends_with($suffix, $routeSuffix) || str_contains($suffix, ".{$routeSuffix}")) {
                return $pageKey;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function folderSegment(array $definition): string
    {
        return trim(str_replace('cms/', '', (string) $definition['legacy_path']), '/');
    }

    /**
     * @return array{label: string, url: string|null}
     */
    private function item(string $label, ?string $url = null): array
    {
        return [
            'label' => $label,
            'url' => $url,
        ];
    }
}
