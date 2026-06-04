<?php

namespace App\Support\Routing;

use App\Models\Cms\CmsRedirect;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Page;
use App\Models\User;
use App\Support\Localization\TranslationRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PublicSlugManager
{
    private const HISTORY_PREFIX = 'Slug history:';

    public function __construct(private readonly TranslationRepository $translations) {}

    public function contentSlug(?string $submittedSlug, string $title, ?ContentItem $contentItem = null): string
    {
        return $this->uniqueSlug(
            $this->baseSlug($submittedSlug, $title, $contentItem?->slug, $contentItem?->title, 'content'),
            $contentItem,
            $this->contentPathResolver($contentItem?->locale),
        );
    }

    public function pageSlug(?string $submittedSlug, string $title, ?Page $page = null): string
    {
        return $this->uniqueSlug(
            $this->baseSlug($submittedSlug, $title, $page?->slug, $page?->title, 'page'),
            $page,
            fn (string $slug): string => $slug,
        );
    }

    public function recordContentSlugChange(
        ContentItem $contentItem,
        ?string $oldSlug,
        ?string $oldLocale,
        ?User $actor = null,
    ): void {
        $newPath = $this->contentPath($contentItem->slug, $contentItem->locale);

        $this->deactivateCurrentSlugHistory($newPath, $contentItem, $actor);

        $oldPath = $this->contentPath($oldSlug, $oldLocale);

        $this->recordSlugHistory(
            $contentItem,
            'Content item',
            $oldPath,
            $newPath,
            $contentItem->title,
            $actor,
        );
    }

    public function recordPageSlugChange(Page $page, ?string $oldSlug, ?User $actor = null): void
    {
        $newPath = $this->pagePath($page->slug);

        $this->deactivateCurrentSlugHistory($newPath, $page, $actor);

        $this->recordSlugHistory(
            $page,
            'Page',
            $this->pagePath($oldSlug),
            $newPath,
            $page->title,
            $actor,
        );
    }

    private function baseSlug(
        ?string $submittedSlug,
        string $title,
        ?string $previousSlug,
        ?string $previousTitle,
        string $fallback,
    ): string {
        $submittedSlug = trim((string) $submittedSlug);
        $titleSlug = Str::slug($title);

        if ($submittedSlug === '') {
            return $titleSlug ?: $fallback;
        }

        if ($previousSlug && $previousTitle && $submittedSlug === $previousSlug && $this->wasGeneratedFromTitle($previousSlug, $previousTitle)) {
            return $titleSlug ?: $fallback;
        }

        return Str::slug($submittedSlug) ?: $titleSlug ?: $fallback;
    }

    private function wasGeneratedFromTitle(string $slug, string $title): bool
    {
        $base = Str::slug($title);

        if ($base === '') {
            return false;
        }

        return $slug === $base || preg_match('/^'.preg_quote($base, '/').'-[0-9]+$/', $slug) === 1;
    }

    /**
     * @param  callable(string): string  $pathResolver
     */
    private function uniqueSlug(string $base, ?Model $owner, callable $pathResolver): string
    {
        $candidate = $base;
        $counter = 2;

        while ($this->isTaken($candidate, $owner, $pathResolver)) {
            $candidate = $base.'-'.$counter++;
        }

        return $candidate;
    }

    /**
     * @param  callable(string): string  $pathResolver
     */
    private function isTaken(string $slug, ?Model $owner, callable $pathResolver): bool
    {
        if (in_array($slug, config('cms.reserved_slugs', []), true)) {
            return true;
        }

        if ($this->pageExists($slug, $owner) || $this->contentItemExists($slug, $owner)) {
            return true;
        }

        $redirect = CmsRedirect::findForPath($pathResolver($slug));

        return $redirect instanceof CmsRedirect && ! $this->isSlugHistoryForOwner($redirect, $owner);
    }

    private function pageExists(string $slug, ?Model $owner): bool
    {
        return Page::query()
            ->where('slug', $slug)
            ->when($owner instanceof Page && $owner->exists, fn ($query) => $query->whereKeyNot($owner->getKey()))
            ->exists();
    }

    private function contentItemExists(string $slug, ?Model $owner): bool
    {
        return ContentItem::query()
            ->where('slug', $slug)
            ->when($owner instanceof ContentItem && $owner->exists, fn ($query) => $query->whereKeyNot($owner->getKey()))
            ->exists();
    }

    private function recordSlugHistory(
        Model $owner,
        string $entityLabel,
        ?string $oldPath,
        string $newPath,
        ?string $title,
        ?User $actor,
    ): void {
        if (! $oldPath || $oldPath === $newPath) {
            return;
        }

        $redirect = CmsRedirect::query()
            ->where('source_path', CmsRedirect::normalizeSourcePath($oldPath))
            ->first() ?? new CmsRedirect;

        if (! $redirect->exists) {
            $redirect->created_by = $actor?->id;
        }

        $redirect->fill([
            'source_path' => CmsRedirect::normalizeSourcePath($oldPath),
            'target_url' => '/'.ltrim($newPath, '/'),
            'description' => self::HISTORY_PREFIX.' '.$this->historyOwnerNeedle($owner).' '.__(':entity ":title" moved from :source to :target.', [
                'entity' => __($entityLabel),
                'title' => $title ?: $owner->getKey(),
                'source' => '/'.ltrim($oldPath, '/'),
                'target' => '/'.ltrim($newPath, '/'),
            ]),
            'status_code' => 301,
            'is_active' => true,
            'preserve_query' => true,
            'updated_by' => $actor?->id,
        ])->save();
    }

    private function deactivateCurrentSlugHistory(string $path, Model $owner, ?User $actor): void
    {
        $redirect = CmsRedirect::findForPath($path);

        if (! $redirect instanceof CmsRedirect || ! $this->isSlugHistoryForOwner($redirect, $owner)) {
            return;
        }

        $redirect->forceFill([
            'target_url' => '/'.ltrim($path, '/'),
            'is_active' => false,
            'updated_by' => $actor?->id,
        ])->save();
    }

    private function isSlugHistoryForOwner(CmsRedirect $redirect, ?Model $owner): bool
    {
        if (! $owner instanceof Model || ! is_string($redirect->description)) {
            return false;
        }

        return Str::startsWith($redirect->description, self::HISTORY_PREFIX)
            && str_contains($redirect->description, $this->historyOwnerNeedle($owner));
    }

    private function historyOwnerNeedle(Model $owner): string
    {
        return match (true) {
            $owner instanceof ContentItem => '[content_item:'.$owner->getKey().']',
            $owner instanceof Page => '[page:'.$owner->getKey().']',
            default => '['.Str::snake(class_basename($owner)).':'.$owner->getKey().']',
        };
    }

    private function pagePath(?string $slug): ?string
    {
        $slug = trim((string) $slug, '/');

        return $slug !== '' ? $slug : null;
    }

    private function contentPath(?string $slug, ?string $locale): ?string
    {
        $slug = trim((string) $slug, '/');

        if ($slug === '') {
            return null;
        }

        $locale = $locale ? $this->translations->normalizeLocale($locale) : null;

        if ($locale && $locale !== $this->translations->defaultLocale()) {
            return $locale.'/'.$slug;
        }

        return $slug;
    }

    /**
     * @return callable(string): string
     */
    private function contentPathResolver(?string $locale): callable
    {
        return fn (string $slug): string => $this->contentPath($slug, $locale) ?? $slug;
    }
}
