<?php

namespace App\Support\Domains;

use App\Models\Cms\CmsLanguage;
use App\Models\Cms\IsoLanguage;
use App\Models\User;
use App\Support\Localization\LocalizationDataRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DomainLanguageActivator
{
    public function __construct(private readonly LocalizationDataRepository $repository) {}

    /**
     * @return Collection<int, CmsLanguage>
     */
    public function options(): Collection
    {
        if (! $this->hasLanguageTable()) {
            return collect();
        }

        try {
            return CmsLanguage::query()
                ->orderByDesc('is_enabled')
                ->orderBy('name')
                ->get();
        } catch (QueryException) {
            return collect();
        }
    }

    public function canResolve(?string $search): bool
    {
        if (! filled($search)) {
            return true;
        }

        return $this->definitionFor($search) !== null;
    }

    public function activateFromSearch(?string $search, ?User $user): ?CmsLanguage
    {
        $definition = $this->definitionFor($search);

        if ($definition === null) {
            return null;
        }

        $language = CmsLanguage::query()
            ->where('code', $definition['code'])
            ->first() ?? new CmsLanguage;

        if (! $language->exists) {
            $language->created_by = $user?->id;
        }

        $language->fill([
            'name' => $definition['name'],
            'slug' => $definition['slug'],
            'code' => $definition['code'],
            'native_name' => $definition['native_name'],
            'direction' => $definition['direction'],
            'source_locale' => $definition['source_locale'],
            'status' => 'active',
            'is_enabled' => true,
            'is_default' => $language->is_default ?: ! CmsLanguage::query()->where('is_default', true)->exists(),
            'sort_order' => $language->sort_order ?? $this->nextSortOrder(),
            'updated_by' => $user?->id,
        ])->save();

        return $language->refresh();
    }

    /**
     * @return array{name:string,slug:string,code:string,native_name:string|null,direction:string,source_locale:string}|null
     */
    private function definitionFor(?string $search): ?array
    {
        $search = trim((string) $search);

        if ($search === '' || ! $this->hasLanguageTable()) {
            return null;
        }

        $language = $this->findCmsLanguage($search, exactOnly: true)
            ?? $this->findIsoLanguage($search, exactOnly: true)
            ?? $this->findRepositoryLanguage($search, exactOnly: true)
            ?? $this->findCmsLanguage($search)
            ?? $this->findIsoLanguage($search)
            ?? $this->findRepositoryLanguage($search);

        if ($language === null) {
            return null;
        }

        return [
            'name' => (string) $language->name,
            'slug' => (string) ($language->slug ?: Str::slug((string) $language->name)),
            'code' => Str::of((string) $language->code)->replace('_', '-')->lower()->toString(),
            'native_name' => $language->native_name ? (string) $language->native_name : null,
            'direction' => (string) ($language->direction ?: CmsLanguage::directionFor((string) $language->code)),
            'source_locale' => (string) ($language->source_locale ?: 'en'),
        ];
    }

    private function findCmsLanguage(string $search, bool $exactOnly = false): ?CmsLanguage
    {
        $normalized = $this->normalizeSearch($search);
        $slug = Str::slug($search);

        $exactMatch = CmsLanguage::query()
            ->where('code', $normalized)
            ->orWhere('slug', $slug)
            ->orWhere('name', $search)
            ->orWhere('native_name', $search)
            ->first();

        if ($exactOnly || $exactMatch) {
            return $exactMatch;
        }

        return CmsLanguage::query()
            ->where('name', 'like', '%'.$this->escapeLike($search).'%')
            ->orWhere('native_name', 'like', '%'.$this->escapeLike($search).'%')
            ->orWhere('code', 'like', '%'.$this->escapeLike($normalized).'%')
            ->orderBy('name')
            ->first();
    }

    private function findIsoLanguage(string $search, bool $exactOnly = false): ?IsoLanguage
    {
        if (! $this->hasIsoLanguageTable()) {
            return null;
        }

        $normalized = $this->normalizeSearch($search);
        $slug = Str::slug($search);

        $exactMatch = IsoLanguage::query()
            ->where('code', $normalized)
            ->orWhere('slug', $slug)
            ->orWhere('name', $search)
            ->orWhere('native_name', $search)
            ->first();

        if ($exactOnly || $exactMatch) {
            return $exactMatch;
        }

        return IsoLanguage::query()
            ->where('name', 'like', '%'.$this->escapeLike($search).'%')
            ->orWhere('native_name', 'like', '%'.$this->escapeLike($search).'%')
            ->orWhere('code', 'like', '%'.$this->escapeLike($normalized).'%')
            ->orderBy('name')
            ->first();
    }

    private function findRepositoryLanguage(string $search, bool $exactOnly = false): ?CmsLanguage
    {
        $normalized = $this->normalizeSearch($search);
        $slug = Str::slug($search);

        $definitions = $this->repository->languages('en');

        $definition = $definitions->first(function (array $definition) use ($search, $normalized, $slug): bool {
            return $definition['code'] === $normalized
                    || $definition['slug'] === $slug
                    || strcasecmp((string) $definition['name'], $search) === 0
                    || strcasecmp((string) $definition['native_name'], $search) === 0;
        });

        if ($exactOnly || $definition) {
            return $definition === null ? null : new CmsLanguage($definition);
        }

        $definition = $definitions->first(function (array $definition) use ($search): bool {
            return str_contains(Str::lower((string) $definition['name']), Str::lower($search))
                || str_contains(Str::lower((string) $definition['native_name']), Str::lower($search));
        });

        return $definition === null ? null : new CmsLanguage($definition);
    }

    private function normalizeSearch(string $search): string
    {
        return Str::of($search)
            ->replace('_', '-')
            ->lower()
            ->toString();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function nextSortOrder(): int
    {
        return ((int) CmsLanguage::query()->max('sort_order')) + 1;
    }

    private function hasLanguageTable(): bool
    {
        try {
            return Schema::hasTable('languages');
        } catch (QueryException) {
            return false;
        }
    }

    private function hasIsoLanguageTable(): bool
    {
        try {
            return Schema::hasTable('iso_languages');
        } catch (QueryException) {
            return false;
        }
    }
}
