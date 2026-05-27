<?php

namespace App\Support\Navigation;

use App\Models\Cms\CatalogCategory;
use App\Models\Cms\CatalogProduct;
use App\Models\Cms\ContentCategory;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Download;
use App\Models\Cms\DownloadCategory;
use App\Models\Cms\Event;
use App\Models\Cms\EventCategory;
use App\Models\Cms\FaqCategory;
use App\Models\Cms\FaqItem;
use App\Models\Cms\Form;
use App\Models\Cms\Location;
use App\Models\Cms\LocationCategory;
use App\Models\Cms\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NavigationLinkRegistry
{
    /**
     * @return list<array{key: string, label: string, is_category: bool}>
     */
    public function typeOptions(): array
    {
        return collect($this->definitions())
            ->filter(fn (array $definition): bool => $definition['key'] === 'custom' || $this->hasTable($definition['model']))
            ->map(fn (array $definition): array => [
                'key' => $definition['key'],
                'label' => __($definition['label']),
                'is_category' => (bool) ($definition['is_category'] ?? false),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function allowedTypes(): array
    {
        return collect($this->typeOptions())->pluck('key')->all();
    }

    public function isCategoryType(string $type): bool
    {
        $definition = $this->definition($type);

        return (bool) ($definition['is_category'] ?? false);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchOptions(string $type, ?string $term): Collection
    {
        $definition = $this->definition($type);

        if (! $definition || $definition['key'] === 'custom' || ! $this->hasTable($definition['model'])) {
            return collect();
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $definition['model'];
        $table = (new $modelClass)->getTable();
        $searchColumns = array_values(array_filter(
            $definition['search_columns'],
            fn (string $column): bool => Schema::hasColumn($table, $column),
        ));
        $query = $modelClass::query();
        $term = trim((string) $term);

        if ($term !== '' && $searchColumns !== []) {
            $query->where(function (Builder $query) use ($searchColumns, $term): void {
                foreach ($searchColumns as $column) {
                    $query->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }

        if (Schema::hasColumn($table, 'sort_order')) {
            $query->orderBy('sort_order');
        }

        $titleColumn = $this->firstExistingColumn($table, $definition['title_columns']);

        if ($titleColumn) {
            $query->orderBy($titleColumn);
        }

        return $query
            ->limit(20)
            ->get()
            ->map(fn (Model $model): array => $this->optionFromModel($definition, $model))
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function optionFor(string $type, mixed $id): ?array
    {
        $definition = $this->definition($type);

        if (! $definition || $definition['key'] === 'custom' || ! is_numeric($id) || ! $this->hasTable($definition['model'])) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $definition['model'];
        $model = $modelClass::query()->find((int) $id);

        return $model instanceof Model ? $this->optionFromModel($definition, $model) : null;
    }

    public function urlFor(string $type, mixed $id = null, ?string $customUrl = null): ?string
    {
        if ($type === 'custom') {
            return $this->normalizeCustomUrl($customUrl);
        }

        $definition = $this->definition($type);

        if (! $definition || ! is_numeric($id) || ! $this->hasTable($definition['model'])) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $definition['model'];
        $model = $modelClass::query()->find((int) $id);

        if (! $model instanceof Model) {
            return null;
        }

        if ($type === 'page') {
            $slug = (string) $model->getAttribute('slug');

            return $slug === 'home'
                ? route('frontend.home')
                : route('frontend.pages.show', ['slug' => $slug]);
        }

        if ($type === 'content_item') {
            return route('frontend.pages.show', ['slug' => $model->getAttribute('slug')]);
        }

        if ($type === 'download') {
            return route('frontend.downloads.show', ['download' => method_exists($model, 'publicRouteKey') ? $model->publicRouteKey() : $model->getKey()]);
        }

        if ($this->isCategoryType($type)) {
            return $this->categoryUrl($model, (string) ($definition['url_prefix'] ?? ''));
        }

        return $this->prefixedSlugUrl($model, (string) ($definition['url_prefix'] ?? ''));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function expandedCategoryChildren(string $type, mixed $id): Collection
    {
        $definition = $this->definition($type);

        if (! $definition || ! $this->isCategoryType($type) || ! is_numeric($id) || ! $this->hasTable($definition['model'])) {
            return collect();
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $definition['model'];
        $category = $modelClass::query()->find((int) $id);

        if (! $category instanceof Model || ! method_exists($category, 'children')) {
            return collect();
        }

        return $this->categoryChildren($category, $definition);
    }

    public function isExternalUrl(?string $url): bool
    {
        if (! is_string($url) || ! preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $knownInternalHosts = collect([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            request()->getHost(),
        ])
            ->filter()
            ->map(fn (string $internalHost): string => Str::lower($internalHost))
            ->unique()
            ->all();

        return ! in_array(Str::lower($host), $knownInternalHosts, true);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function definition(string $type): ?array
    {
        return collect($this->definitions())->firstWhere('key', $type);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            [
                'key' => 'custom',
                'label' => 'Custom URL',
                'model' => null,
                'title_columns' => [],
                'search_columns' => [],
                'is_category' => false,
                'url_prefix' => '',
            ],
            [
                'key' => 'page',
                'label' => 'Page',
                'model' => Page::class,
                'title_columns' => ['navigation_label', 'title'],
                'search_columns' => ['title', 'navigation_label', 'slug'],
                'is_category' => false,
                'url_prefix' => '',
            ],
            [
                'key' => 'content_category',
                'label' => 'Content category',
                'model' => ContentCategory::class,
                'title_columns' => ['name'],
                'search_columns' => ['name', 'slug', 'description'],
                'is_category' => true,
                'url_prefix' => '',
            ],
            [
                'key' => 'content_item',
                'label' => 'Content item',
                'model' => ContentItem::class,
                'title_columns' => ['title'],
                'search_columns' => ['title', 'slug', 'intro'],
                'is_category' => false,
                'url_prefix' => '',
            ],
            [
                'key' => 'catalog_category',
                'label' => 'Catalog category',
                'model' => CatalogCategory::class,
                'title_columns' => ['name'],
                'search_columns' => ['name', 'slug', 'description'],
                'is_category' => true,
                'url_prefix' => '',
            ],
            [
                'key' => 'catalog_product',
                'label' => 'Catalog product',
                'model' => CatalogProduct::class,
                'title_columns' => ['name'],
                'search_columns' => ['name', 'slug', 'sku', 'description'],
                'is_category' => false,
                'url_prefix' => 'products',
            ],
            [
                'key' => 'event_category',
                'label' => 'Event category',
                'model' => EventCategory::class,
                'title_columns' => ['name'],
                'search_columns' => ['name', 'slug', 'description'],
                'is_category' => true,
                'url_prefix' => 'events',
            ],
            [
                'key' => 'event',
                'label' => 'Event',
                'model' => Event::class,
                'title_columns' => ['title'],
                'search_columns' => ['title', 'slug', 'intro', 'location'],
                'is_category' => false,
                'url_prefix' => 'events',
            ],
            [
                'key' => 'download_category',
                'label' => 'Download category',
                'model' => DownloadCategory::class,
                'title_columns' => ['name'],
                'search_columns' => ['name', 'slug', 'description'],
                'is_category' => true,
                'url_prefix' => 'downloads',
            ],
            [
                'key' => 'download',
                'label' => 'Download',
                'model' => Download::class,
                'title_columns' => ['name'],
                'search_columns' => ['name', 'slug', 'description'],
                'is_category' => false,
                'url_prefix' => 'downloads',
            ],
            [
                'key' => 'faq_category',
                'label' => 'FAQ category',
                'model' => FaqCategory::class,
                'title_columns' => ['name'],
                'search_columns' => ['name', 'slug', 'description'],
                'is_category' => true,
                'url_prefix' => 'faq',
            ],
            [
                'key' => 'faq_item',
                'label' => 'FAQ item',
                'model' => FaqItem::class,
                'title_columns' => ['question'],
                'search_columns' => ['question', 'slug', 'answer'],
                'is_category' => false,
                'url_prefix' => 'faq',
            ],
            [
                'key' => 'location_category',
                'label' => 'Location category',
                'model' => LocationCategory::class,
                'title_columns' => ['name'],
                'search_columns' => ['name', 'slug', 'description'],
                'is_category' => true,
                'url_prefix' => 'locations',
            ],
            [
                'key' => 'location',
                'label' => 'Location',
                'model' => Location::class,
                'title_columns' => ['name'],
                'search_columns' => ['name', 'slug', 'city', 'description'],
                'is_category' => false,
                'url_prefix' => 'locations',
            ],
            [
                'key' => 'form',
                'label' => 'Form',
                'model' => Form::class,
                'title_columns' => ['name'],
                'search_columns' => ['name', 'slug', 'description'],
                'is_category' => false,
                'url_prefix' => 'forms',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function optionFromModel(array $definition, Model $model): array
    {
        $title = $this->titleFor($definition, $model);
        $url = $this->urlFor((string) $definition['key'], $model->getKey());

        return [
            'id' => $model->getKey(),
            'type' => $definition['key'],
            'type_label' => __((string) $definition['label']),
            'label' => $title,
            'title' => $title,
            'url' => $url,
            'is_category' => (bool) ($definition['is_category'] ?? false),
            'status' => $model->getAttribute('status'),
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function titleFor(array $definition, Model $model): string
    {
        foreach ($definition['title_columns'] as $column) {
            $value = $model->getAttribute($column);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        $slug = $model->getAttribute('slug');

        return is_string($slug) && $slug !== ''
            ? Str::headline($slug)
            : '#'.$model->getKey();
    }

    private function normalizeCustomUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (preg_match('/^(https?:\/\/|mailto:|tel:|#|\/)/i', $url) === 1) {
            return $url;
        }

        return '/'.ltrim($url, '/');
    }

    private function prefixedSlugUrl(Model $model, string $prefix): ?string
    {
        $slug = $model->getAttribute('slug');

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        return url('/'.trim($prefix.'/'.$slug, '/'));
    }

    private function categoryUrl(Model $category, string $prefix): ?string
    {
        $customUrl = $category->getAttribute('custom_url');

        if (is_string($customUrl) && trim($customUrl) !== '') {
            return $this->normalizeCustomUrl($customUrl);
        }

        $segments = $this->categorySegments($category);

        if ($segments === []) {
            return null;
        }

        return url('/'.trim($prefix.'/'.implode('/', $segments), '/'));
    }

    /**
     * @return list<string>
     */
    private function categorySegments(Model $category): array
    {
        $segments = [];
        $current = $category;

        while ($current instanceof Model) {
            $label = $current->getAttribute('slug') ?: $current->getAttribute('name') ?: $current->getKey();
            array_unshift($segments, Str::slug((string) $label));
            $current = method_exists($current, 'parent') ? $current->parent()->first() : null;
        }

        return array_values(array_filter($segments));
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return Collection<int, array<string, mixed>>
     */
    private function categoryChildren(Model $category, array $definition): Collection
    {
        $query = $category->children();
        $table = $category->getTable();

        if (Schema::hasColumn($table, 'status')) {
            $query->where('status', 'active');
        }

        if (Schema::hasColumn($table, 'is_hidden_from_navigation')) {
            $query->where('is_hidden_from_navigation', false);
        }

        return $query
            ->get()
            ->map(function (Model $child) use ($definition): array {
                $url = $this->categoryUrl($child, (string) ($definition['url_prefix'] ?? ''));

                return [
                    'title' => $this->titleFor($definition, $child),
                    'url' => $url,
                    'target_blank' => $this->isExternalUrl($url),
                    'external' => $this->isExternalUrl($url),
                    'children' => $this->categoryChildren($child, $definition)->all(),
                ];
            })
            ->values();
    }

    /**
     * @param  class-string<Model>|null  $modelClass
     */
    private function hasTable(?string $modelClass): bool
    {
        if (! is_string($modelClass)) {
            return false;
        }

        return Schema::hasTable((new $modelClass)->getTable());
    }

    /**
     * @param  list<string>  $columns
     */
    private function firstExistingColumn(string $table, array $columns): ?string
    {
        return collect($columns)->first(fn (string $column): bool => Schema::hasColumn($table, $column));
    }
}
