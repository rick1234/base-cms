<?php

namespace App\Support\Admin\Dashboard;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DashboardNavigationBuilder
{
    /**
     * The legacy CMS dashboard groups screens under visual module headers.
     *
     * @var array<string, array<int, array{title: string, icon: string, screens: array<int, string>}>>
     */
    private const MODULE_TREE = [
        'content' => [
            ['title' => 'Navigation', 'icon' => 'website', 'screens' => ['navigation']],
            ['title' => 'Pages', 'icon' => 'content', 'screens' => ['content_items', 'content_categories']],
            ['title' => 'Forms', 'icon' => 'form', 'screens' => ['forms', 'form_categories']],
        ],
        'modules' => [
            ['title' => 'FAQ', 'icon' => 'faq', 'screens' => ['faq_items', 'faq_categories']],
            ['title' => 'Vacancies', 'icon' => 'vacatures', 'screens' => ['vacancies', 'vacancy_categories']],
            ['title' => 'Events', 'icon' => 'evenementen', 'screens' => ['events', 'event_categories']],
            ['title' => 'Downloads', 'icon' => 'download', 'screens' => ['downloads', 'download_categories']],
            ['title' => 'Locations', 'icon' => 'vestigingen', 'screens' => ['locations', 'location_categories']],
        ],
        'commerce' => [
            ['title' => 'Catalog', 'icon' => 'catalogus', 'screens' => ['catalog_products', 'catalog_categories', 'catalog_brands']],
            ['title' => 'Promotion', 'icon' => 'promotie', 'screens' => ['catalog_reviews', 'catalog_coupons', 'catalog_promotions']],
            ['title' => 'Orders', 'icon' => 'orders', 'screens' => ['orders', 'order_exports', 'order_delivery_dates', 'order_payment_methods']],
        ],
        'media' => [
            ['title' => 'Banners', 'icon' => 'banner', 'screens' => ['banners', 'banner_categories']],
            ['title' => 'Sliders', 'icon' => 'slider', 'screens' => ['sliders', 'slider_categories']],
        ],
        'users' => [
            ['title' => 'Users', 'icon' => 'users', 'screens' => ['users', 'user_categories']],
            ['title' => 'Roles and Permissions', 'icon' => 'roles', 'screens' => ['roles']],
        ],
        'seo' => [
            ['title' => 'SEO', 'icon' => 'redirect', 'screens' => ['redirects', 'urls', 'url_references']],
        ],
        'language' => [
            ['title' => 'Translations', 'icon' => 'translations', 'screens' => ['translations']],
            ['title' => 'Countries', 'icon' => 'countries', 'screens' => ['countries']],
        ],
        'configuration' => [
            ['title' => 'Configuration', 'icon' => 'settings', 'screens' => ['domains', 'website_templates']],
        ],
        'website' => [
            ['title' => 'Website', 'icon' => 'website', 'screens' => ['guestbook']],
        ],
    ];

    /**
     * @return Collection<int, array{key: string, title: string, icon: string, modules: Collection<int, array{title: string, icon: string, theme: string, links: Collection<int, array{title: string, url: string, icon: string}>}>}>
     */
    public function build(bool $legacyRoutes = false): Collection
    {
        $screens = collect(config('cms_modules.screens'));
        $groups = collect(config('cms_modules.groups'));
        $mappedScreens = collect(self::MODULE_TREE)
            ->flatMap(fn (array $modules): array => collect($modules)->flatMap(fn (array $module): array => $module['screens'])->all())
            ->unique();

        $dashboardGroups = collect(self::MODULE_TREE)
            ->map(function (array $modules, string $groupKey) use ($groups, $legacyRoutes, $screens): ?array {
                $builtModules = collect($modules)
                    ->map(fn (array $module): ?array => $this->buildModule($module, $screens, $legacyRoutes))
                    ->filter()
                    ->values();

                if ($builtModules->isEmpty()) {
                    return null;
                }

                return [
                    'key' => $groupKey,
                    'title' => $groups->get($groupKey, Str::headline($groupKey)),
                    'icon' => $this->materialIcon('module'),
                    'modules' => $builtModules,
                ];
            })
            ->filter()
            ->values();

        return $this->appendUnmappedScreens($dashboardGroups, $screens->except($mappedScreens->all()), $groups, $legacyRoutes);
    }

    /**
     * @return Collection<int, array{title: string, icon: string, theme: string, url: string, overview_title: string, overview_screen: string|null, subitems: Collection<int, array{title: string, url: string, icon: string, screen_key: string}>}>
     */
    public function moduleList(bool $legacyRoutes = false): Collection
    {
        return $this->build($legacyRoutes)
            ->flatMap(fn (array $group): Collection => $group['modules'])
            ->map(function (array $module): ?array {
                $overview = $module['links']->first();

                if (! $overview) {
                    return null;
                }

                return [
                    'title' => $module['title'],
                    'icon' => $module['icon'],
                    'theme' => $module['theme'],
                    'url' => $overview['url'],
                    'overview_title' => $overview['title'],
                    'overview_screen' => $overview['screen_key'] ?? null,
                    'subitems' => $module['links']->skip(1)->values(),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  array{title: string, icon: string, screens: array<int, string>}  $module
     * @param  Collection<string, array<string, mixed>>  $screens
     * @return array{title: string, icon: string, theme: string, links: Collection<int, array{title: string, url: string, icon: string}>}|null
     */
    private function buildModule(array $module, Collection $screens, bool $legacyRoutes): ?array
    {
        $links = collect($module['screens'])
            ->map(fn (string $screenKey): ?array => $this->buildLink($screenKey, $screens, $legacyRoutes, $module['icon']))
            ->filter()
            ->values();

        if ($links->isEmpty()) {
            return null;
        }

        return [
            'title' => $module['title'],
            'icon' => $this->materialIcon($module['icon']),
            'theme' => $this->moduleTheme($module['icon']),
            'links' => $links,
        ];
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $screens
     * @return array{title: string, url: string, icon: string, screen_key: string}|null
     */
    private function buildLink(string $screenKey, Collection $screens, bool $legacyRoutes, string $fallbackIcon): ?array
    {
        /** @var array<string, mixed>|null $screen */
        $screen = $screens->get($screenKey);

        if ($screen === null) {
            return null;
        }

        if (! $legacyRoutes && isset($screen['admin_route']) && is_string($screen['admin_route'])) {
            return [
                'screen_key' => $screenKey,
                'title' => (string) data_get($screen, 'pages.index.name', $screen['name']),
                'url' => route($screen['admin_route']),
                'icon' => $this->materialIcon($fallbackIcon),
            ];
        }

        $path = $this->adminPath((string) $screen['legacy_path']);
        $routeName = $legacyRoutes ? 'cms.modules.show' : 'admin.modules.show';

        return [
            'screen_key' => $screenKey,
            'title' => (string) data_get($screen, 'pages.index.name', $screen['name']),
            'url' => route($routeName, $path),
            'icon' => $this->materialIcon($fallbackIcon),
        ];
    }

    /**
     * @param  Collection<int, array{key: string, title: string, icon: string, modules: Collection<int, array{title: string, icon: string, theme: string, links: Collection<int, array{title: string, url: string, icon: string}>}>}>  $dashboardGroups
     * @param  Collection<string, array<string, mixed>>  $unmappedScreens
     * @param  Collection<string, string>  $groups
     * @return Collection<int, array{key: string, title: string, icon: string, modules: Collection<int, array{title: string, icon: string, theme: string, links: Collection<int, array{title: string, url: string, icon: string}>}>}>
     */
    private function appendUnmappedScreens(Collection $dashboardGroups, Collection $unmappedScreens, Collection $groups, bool $legacyRoutes): Collection
    {
        $unmappedScreens
            ->groupBy(fn (array $screen): string => (string) $screen['group'], true)
            ->each(function (Collection $groupScreens, string $groupKey) use ($dashboardGroups, $groups, $legacyRoutes): void {
                $fallbackModules = $groupScreens
                    ->groupBy(fn (array $screen): string => $this->firstPathSegment((string) $screen['legacy_path']), true)
                    ->map(function (Collection $moduleScreens, string $segment) use ($legacyRoutes): array {
                        $icon = $this->materialIcon($segment);

                        return [
                            'title' => Str::headline($segment),
                            'icon' => $icon,
                            'theme' => $this->moduleTheme($segment),
                            'links' => $moduleScreens
                                ->keys()
                                ->map(fn (string $screenKey): ?array => $this->buildLink($screenKey, $moduleScreens, $legacyRoutes, $segment))
                                ->filter()
                                ->values(),
                        ];
                    })
                    ->filter(fn (array $module): bool => $module['links']->isNotEmpty())
                    ->values();

                if ($fallbackModules->isEmpty()) {
                    return;
                }

                $existingGroup = $dashboardGroups->search(fn (array $group): bool => $group['key'] === $groupKey);

                if ($existingGroup === false) {
                    $dashboardGroups->push([
                        'key' => $groupKey,
                        'title' => $groups->get($groupKey, Str::headline($groupKey)),
                        'icon' => $this->materialIcon('module'),
                        'modules' => $fallbackModules,
                    ]);

                    return;
                }

                $fallbackModules->each(fn (array $module): Collection => $dashboardGroups[$existingGroup]['modules']->push($module));
            });

        return $dashboardGroups->values();
    }

    private function adminPath(string $legacyPath): string
    {
        return trim(Str::after($legacyPath, 'cms/'), '/');
    }

    private function firstPathSegment(string $legacyPath): string
    {
        return Str::before($this->adminPath($legacyPath), '/');
    }

    private function materialIcon(string $name): string
    {
        $icon = match ($name) {
            'actiecodes' => 'actiecodes',
            'afleverdata' => 'afleverdata',
            'betaalmethoden' => 'betaalmethoden',
            'catalogus' => 'catalogus',
            'domein' => 'domein',
            'evenementen' => 'evenementen',
            'form' => 'form',
            'landen' => 'countries',
            'vacatures' => 'vacatures',
            'vestigingen' => 'vestigingen',
            default => $name,
        };

        $key = Str::of($icon)
            ->lower()
            ->replace(['_', '/'], '-')
            ->trim('-')
            ->toString();

        return (string) config("cms_icons.modules.{$key}", config('cms_icons.fallback', 'extension'));
    }

    private function moduleTheme(string $name): string
    {
        $theme = match ($name) {
            'banner' => 'banners',
            'catalogus' => 'catalog',
            'evenementen' => 'events',
            'form' => 'forms',
            'promotie' => 'promotion',
            'redirect' => 'seo',
            'settings' => 'configuration',
            'vacatures' => 'vacancies',
            'vestigingen' => 'locations',
            default => $name,
        };

        return Str::of($theme)
            ->lower()
            ->replace(['_', '/'], '-')
            ->trim('-')
            ->toString();
    }
}
