<?php

namespace App\Support\Cms;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CmsModuleRegistry
{
    /**
     * @return Collection<string, array<string, mixed>>
     */
    public function modules(): Collection
    {
        return collect(config('cms_modules.modules'))
            ->map(fn (array $module, string $handle): array => $this->normalizeDefinition($module, $handle));
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    public function screens(): Collection
    {
        return collect(config('cms_modules.screens'))
            ->map(fn (array $screen, string $handle): array => $this->normalizeDefinition($screen, $handle));
    }

    /**
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    public function groupedModules(): Collection
    {
        return $this->groupedScreens();
    }

    /**
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    public function groupedScreens(): Collection
    {
        return $this->screens()
            ->groupBy('group')
            ->map(fn (Collection $screens): Collection => $screens->sortBy('name')->values());
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $handleOrFolder): array
    {
        return $this->findOptional($handleOrFolder) ?? abort(404);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOptional(string $handleOrFolder): ?array
    {
        return $this->findScreen($handleOrFolder)
            ?? $this->findModule($handleOrFolder);
    }

    /**
     * @return array<string, mixed>
     */
    public function findPage(string $handleOrFolder, string $page): array
    {
        $screen = $this->find($handleOrFolder);
        $pageKey = $this->normalizePageKey($page);
        $pages = $screen['pages'] ?? [];

        abort_unless(isset($pages[$pageKey]), 404);

        return $this->normalizePage($screen, $pageKey, $pages[$pageKey]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLegacyClass(string $legacyClass): ?array
    {
        $handle = config("cms_modules.legacy_classes.$legacyClass");

        if (! is_string($handle)) {
            return null;
        }

        return $this->find($handle);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return Collection<int, array<string, mixed>>
     */
    public function pagesFor(array $definition): Collection
    {
        $screen = $this->find($definition['screen_handle'] ?? $definition['handle']);

        return collect($screen['pages'] ?? [])
            ->map(fn (array $page, string $pageKey): array => $this->normalizePage($screen, $pageKey, $page))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function rows(array $definition): LengthAwarePaginator
    {
        if (! Schema::hasTable($definition['table'])) {
            return new LengthAwarePaginator([], 0, 25);
        }

        $query = DB::table($definition['table']);

        if (Schema::hasColumn($definition['table'], 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (Schema::hasColumn($definition['table'], 'id')) {
            $query->latest('id');
        }

        return $query->paginate(25);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function isEditable(array $definition): bool
    {
        return ! ($definition['read_only'] ?? false)
            && Schema::hasTable($definition['table']);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return list<string>
     */
    public function editableColumns(array $definition): array
    {
        if (! $this->isEditable($definition)) {
            return [];
        }

        $guarded = array_merge([
            'id',
            'uuid',
            'legacy_id',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
            'deleted_at',
            'password',
            'remember_token',
            'email_verified_at',
        ], $definition['guarded_columns'] ?? []);

        return array_values(array_filter(
            Schema::getColumnListing($definition['table']),
            fn (string $column): bool => ! in_array($column, $guarded, true),
        ));
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function findRecord(array $definition, int $id): ?object
    {
        if (! Schema::hasTable($definition['table']) || ! Schema::hasColumn($definition['table'], 'id')) {
            return null;
        }

        $query = DB::table($definition['table'])->where('id', $id);

        if (Schema::hasColumn($definition['table'], 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->first();
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $data
     */
    public function createRecord(array $definition, array $data, ?int $userId): int
    {
        abort_unless($this->isEditable($definition), 403);

        $payload = $this->payloadFor($definition, $data, $userId);

        return DB::table($definition['table'])->insertGetId($payload);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $data
     */
    public function updateRecord(array $definition, int $id, array $data, ?int $userId): void
    {
        abort_unless($this->isEditable($definition), 403);

        $payload = $this->payloadFor($definition, $data, $userId, false);

        DB::table($definition['table'])->where('id', $id)->update($payload);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function deleteRecord(array $definition, int $id, ?int $userId): void
    {
        abort_unless($this->isEditable($definition), 403);

        if (Schema::hasColumn($definition['table'], 'deleted_at')) {
            $payload = ['deleted_at' => now()];

            if (Schema::hasColumn($definition['table'], 'updated_by')) {
                $payload['updated_by'] = $userId;
            }

            if (Schema::hasColumn($definition['table'], 'updated_at')) {
                $payload['updated_at'] = now();
            }

            DB::table($definition['table'])->where('id', $id)->update($payload);

            return;
        }

        DB::table($definition['table'])->where('id', $id)->delete();
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return list<string>
     */
    public function columns(array $definition): array
    {
        if (! Schema::hasTable($definition['table'])) {
            return [];
        }

        $available = Schema::getColumnListing($definition['table']);
        $preferred = array_unique([
            'id',
            $definition['title_column'],
            'title',
            'name',
            'question',
            'order_number',
            'email',
            'source_path',
            'target_path',
            'path',
            'host',
            'keyword',
            'date',
            'day',
            'code',
            'status',
            'is_active',
            'created_at',
            'updated_at',
        ]);

        $columns = array_values(array_filter(
            $preferred,
            fn (string $column): bool => in_array($column, $available, true),
        ));

        if (count($columns) > 1) {
            return $columns;
        }

        return array_slice($available, 0, 6);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function folderSegment(array $definition): string
    {
        return trim(str_replace('cms/', '', $definition['legacy_path']), '/');
    }

    private function normalizePageKey(string $page): string
    {
        return Str::beforeLast($page, '.php');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findScreen(string $handleOrFolder): ?array
    {
        $normalized = trim($handleOrFolder, '/');
        $screens = config('cms_modules.screens', []);

        if (isset($screens[$normalized])) {
            return $this->normalizeDefinition($screens[$normalized], $normalized);
        }

        foreach ($screens as $handle => $screen) {
            if ($this->folderSegment($screen) === $normalized) {
                return $this->normalizeDefinition($screen, $handle);
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findModule(string $handleOrFolder): ?array
    {
        $normalized = trim($handleOrFolder, '/');
        $modules = config('cms_modules.modules', []);

        if (isset($modules[$normalized])) {
            return $this->normalizeDefinition($modules[$normalized], $normalized);
        }

        foreach ($modules as $handle => $module) {
            if ($this->folderSegment($module) === $normalized) {
                return $this->normalizeDefinition($module, $handle);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function normalizeDefinition(array $definition, string $handle): array
    {
        $definition = [
            ...$definition,
            'handle' => $handle,
            'folder' => $this->folderSegment($definition),
        ];

        return [
            ...$definition,
            'name' => __((string) $definition['name']),
            'description' => isset($definition['description']) ? __((string) $definition['description']) : null,
            'count' => $this->count($definition),
            'has_table' => Schema::hasTable($definition['table']),
        ];
    }

    /**
     * @param  array<string, mixed>  $screen
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    private function normalizePage(array $screen, string $pageKey, array $page): array
    {
        $definition = [
            ...Arr::except($screen, ['count', 'has_table']),
            ...Arr::except($page, ['type']),
            'handle' => $screen['handle'],
            'folder' => $screen['folder'],
            'name' => __((string) ($page['name'] ?? $screen['name'])),
            'screen_name' => __((string) $screen['name']),
            'screen_handle' => $screen['handle'],
            'screen_folder' => $screen['folder'],
            'page_key' => $pageKey,
            'page_file' => $pageKey.'.php',
            'page_type' => $page['type'] ?? 'utility',
            'table' => $page['table'] ?? $screen['table'],
            'title_column' => $page['title_column'] ?? $screen['title_column'],
            'legacy_tables' => $page['legacy_tables'] ?? $screen['legacy_tables'],
            'read_only' => ($screen['read_only'] ?? false) || ($page['read_only'] ?? false),
        ];

        return [
            ...$definition,
            'description' => isset($definition['description']) ? __((string) $definition['description']) : null,
            'count' => $this->count($definition),
            'has_table' => Schema::hasTable($definition['table']),
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function count(array $definition): int
    {
        if (! Schema::hasTable($definition['table'])) {
            return 0;
        }

        $query = DB::table($definition['table']);

        if (Schema::hasColumn($definition['table'], 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->count();
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payloadFor(array $definition, array $data, ?int $userId, bool $creating = true): array
    {
        $columns = Schema::getColumnListing($definition['table']);
        $editableColumns = $this->editableColumns($definition);
        $payload = collect($data)->only($editableColumns)->all();

        foreach ($editableColumns as $column) {
            if (str_starts_with($column, 'is_') || str_starts_with($column, 'can_') || $column === 'preserve_query') {
                $payload[$column] = (bool) ($payload[$column] ?? false);
            }
        }

        foreach ($editableColumns as $column) {
            if (! array_key_exists($column, $payload) || ! is_string($payload[$column])) {
                continue;
            }

            if ($this->isJsonColumn($column) && trim($payload[$column]) === '') {
                $payload[$column] = null;
            }
        }

        if ($creating && in_array('uuid', $columns, true)) {
            $payload['uuid'] = (string) Str::uuid();
        }

        if ($creating && in_array('created_by', $columns, true)) {
            $payload['created_by'] = $userId;
        }

        if (in_array('updated_by', $columns, true)) {
            $payload['updated_by'] = $userId;
        }

        if ($creating && in_array('created_at', $columns, true)) {
            $payload['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $payload['updated_at'] = now();
        }

        return $payload;
    }

    private function isJsonColumn(string $column): bool
    {
        return in_array($column, [
            'metadata',
            'settings',
            'payload',
            'billing_address',
            'shipping_address',
            'validation_rules',
            'configuration',
            'columns',
        ], true);
    }
}
