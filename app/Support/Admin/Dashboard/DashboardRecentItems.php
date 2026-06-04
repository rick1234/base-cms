<?php

namespace App\Support\Admin\Dashboard;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DashboardRecentItems
{
    private const VIEWED_SESSION_KEY = 'admin_recent_viewed_items';

    /**
     * @return array{viewed: Collection<int, array<string, mixed>>, updated: Collection<int, array<string, mixed>>, created: Collection<int, array<string, mixed>>}
     */
    public function lists(bool $legacyRoutes = false): array
    {
        return [
            'viewed' => $this->viewedItems(),
            'updated' => $this->recordsFor('updated_at', $legacyRoutes),
            'created' => $this->recordsFor('created_at', $legacyRoutes),
        ];
    }

    public function rememberViewedFromRequest(Request $request): void
    {
        if (! $request->isMethod('GET') || ! $request->hasSession()) {
            return;
        }

        $screenKey = $this->screenKeyFromRequest($request);
        $screen = $screenKey ? $this->screens()->get($screenKey) : null;
        $recordId = $this->recordIdFromRequest($request);

        if (! $screen || ! $recordId) {
            return;
        }

        $item = $this->itemFromRecord($screenKey, $screen, $recordId, $request->routeIs('cms.*'), 'viewed', now());

        if (! $item) {
            return;
        }

        $storedItems = collect($request->session()->get(self::VIEWED_SESSION_KEY, []))
            ->reject(fn (array $stored): bool => ($stored['key'] ?? null) === $item['key'])
            ->prepend([
                'key' => $item['key'],
                'title' => $item['title'],
                'module' => $item['module'],
                'icon' => $item['icon'],
                'theme' => $item['theme'],
                'url' => $item['url'],
                'occurred_at' => $item['occurred_at']->toIso8601String(),
            ])
            ->take(20)
            ->values()
            ->all();

        $request->session()->put(self::VIEWED_SESSION_KEY, $storedItems);
    }

    private function viewedItems(): Collection
    {
        return collect(session(self::VIEWED_SESSION_KEY, []))
            ->map(function (array $item): array {
                $item['occurred_at'] = $this->date($item['occurred_at'] ?? null);

                return $item;
            })
            ->take(5)
            ->values();
    }

    private function recordsFor(string $timestampColumn, bool $legacyRoutes): Collection
    {
        return $this->screens()
            ->flatMap(function (array $screen, string $screenKey) use ($timestampColumn, $legacyRoutes): Collection {
                $table = (string) ($screen['table'] ?? '');

                if (! $table || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id') || ! Schema::hasColumn($table, $timestampColumn)) {
                    return collect();
                }

                $titleColumn = (string) ($screen['title_column'] ?? '');
                $hasTitleColumn = $titleColumn && Schema::hasColumn($table, $titleColumn);
                $query = DB::table($table)
                    ->select('id', $timestampColumn.' as occurred_at')
                    ->whereNotNull($timestampColumn)
                    ->orderByDesc($timestampColumn)
                    ->limit(5);

                if ($hasTitleColumn) {
                    $query->addSelect($titleColumn.' as title');
                }

                if (Schema::hasColumn($table, 'deleted_at')) {
                    $query->whereNull('deleted_at');
                }

                if ($timestampColumn === 'updated_at' && Schema::hasColumn($table, 'created_at')) {
                    $query->whereColumn('updated_at', '>', 'created_at');
                }

                return $query
                    ->get()
                    ->map(fn (object $record): ?array => $this->itemFromRow($screenKey, $screen, $record, $legacyRoutes, $timestampColumn === 'created_at' ? 'created' : 'updated'))
                    ->filter();
            })
            ->sortByDesc(fn (array $item): int => $item['occurred_at'] instanceof CarbonInterface ? $item['occurred_at']->getTimestamp() : 0)
            ->take(5)
            ->values();
    }

    private function itemFromRecord(string $screenKey, array $screen, int $recordId, bool $legacyRoutes, string $type, mixed $occurredAt): ?array
    {
        $table = (string) ($screen['table'] ?? '');

        if (! $table || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
            return null;
        }

        $titleColumn = (string) ($screen['title_column'] ?? '');
        $hasTitleColumn = $titleColumn && Schema::hasColumn($table, $titleColumn);
        $query = DB::table($table)
            ->select('id')
            ->where('id', $recordId);

        if ($hasTitleColumn) {
            $query->addSelect($titleColumn.' as title');
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $record = $query->first();

        return $record ? $this->itemFromRow($screenKey, $screen, $record, $legacyRoutes, $type, $occurredAt) : null;
    }

    private function itemFromRow(string $screenKey, array $screen, object $record, bool $legacyRoutes, string $type, mixed $occurredAt = null): ?array
    {
        $id = (int) ($record->id ?? 0);

        if ($id <= 0) {
            return null;
        }

        $title = trim((string) ($record->title ?? ''));
        $moduleTitle = (string) ($screen['name'] ?? Str::headline($screenKey));

        if ($title === '') {
            $title = __($moduleTitle).' #'.$id;
        }

        return [
            'key' => $screenKey.':'.$id,
            'type' => $type,
            'title' => $title,
            'module' => $moduleTitle,
            'icon' => $this->materialIcon($screen),
            'theme' => $this->theme($screen),
            'url' => $this->editUrl($screen, $id, $legacyRoutes),
            'occurred_at' => $this->date($occurredAt ?? ($record->occurred_at ?? null)),
        ];
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    private function screens(): Collection
    {
        return collect(config('cms_modules.screens'));
    }

    private function screenKeyFromRequest(Request $request): ?string
    {
        $screenKey = $request->route()?->getAction('admin_screen');

        if (is_array($screenKey)) {
            $screenKey = end($screenKey);
        }

        return is_string($screenKey) && $screenKey !== '' ? $screenKey : null;
    }

    private function recordIdFromRequest(Request $request): ?int
    {
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model) {
                return (int) $parameter->getKey();
            }

            if (is_numeric($parameter)) {
                return (int) $parameter;
            }
        }

        $routeName = (string) $request->route()?->getName();
        $queryId = $request->integer('id');

        return $queryId > 0 && Str::contains($routeName, 'edit') ? $queryId : null;
    }

    private function editUrl(array $screen, int $id, bool $legacyRoutes): string
    {
        $path = $this->adminPath((string) $screen['legacy_path']);

        if ($legacyRoutes) {
            return route('cms.modules.edit', ['modulePath' => $path, 'record' => $id]);
        }

        $adminRoute = $screen['admin_route'] ?? null;
        $editRoute = is_string($adminRoute) ? Str::replaceLast('.index', '.edit', $adminRoute) : null;

        if ($editRoute && Route::has($editRoute)) {
            return route($editRoute, $id);
        }

        return route('admin.modules.edit', ['modulePath' => $path, 'record' => $id]);
    }

    private function adminPath(string $legacyPath): string
    {
        return trim(Str::after($legacyPath, 'cms/'), '/');
    }

    private function materialIcon(array $screen): string
    {
        $segment = Str::of($this->adminPath((string) $screen['legacy_path']))
            ->before('/')
            ->replace(['_', '/'], '-')
            ->lower()
            ->toString();

        return (string) config("cms_icons.modules.{$segment}", config('cms_icons.fallback', 'extension'));
    }

    private function theme(array $screen): string
    {
        return Str::of((string) ($screen['group'] ?? 'module'))
            ->lower()
            ->replace(['_', '/'], '-')
            ->trim('-')
            ->toString();
    }

    private function date(mixed $value): CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        return $value ? CarbonImmutable::parse($value) : CarbonImmutable::now();
    }
}
