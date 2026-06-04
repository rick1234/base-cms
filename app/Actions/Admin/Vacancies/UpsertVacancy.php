<?php

namespace App\Actions\Admin\Vacancies;

use App\Models\Cms\Vacancy;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UpsertVacancy
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?User $user, ?Vacancy $vacancy = null): Vacancy
    {
        $vacancy ??= new Vacancy;
        $creating = ! $vacancy->exists;

        $attributes = Arr::only($data, [
            'title',
            'locale',
            'body',
            'meta_description',
            'meta_title',
            'form_id',
            'status',
            'active_from',
            'active_until',
            'sort_order',
        ]);

        $attributes['slug'] = $this->slug($data['slug'] ?? null, (string) $attributes['title'], $vacancy);
        $attributes['locale'] ??= app()->getLocale();
        $attributes['metadata'] = $this->metadataFor($vacancy, $data);
        $attributes['updated_by'] = $user?->id;

        if ($creating) {
            $attributes['created_by'] = $user?->id;
        }

        $vacancy->fill($attributes)->save();

        $this->syncCategories($vacancy, (array) ($data['categories'] ?? []));

        return $vacancy->refresh();
    }

    /**
     * @param  array<int|string, mixed>  $categoryIds
     */
    private function syncCategories(Vacancy $vacancy, array $categoryIds): void
    {
        $ids = collect($categoryIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $vacancy->categories()->sync(
            $ids->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index + 1]])->all()
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function metadataFor(Vacancy $vacancy, array $data): ?array
    {
        $metadata = (array) ($vacancy->metadata ?? []);

        foreach (['location', 'employment_type', 'hours', 'salary', 'contact_email'] as $key) {
            $value = $data[$key] ?? null;

            if (filled($value)) {
                $metadata[$key] = trim((string) $value);

                continue;
            }

            unset($metadata[$key]);
        }

        return $metadata === [] ? null : $metadata;
    }

    private function slug(mixed $submittedSlug, string $title, Vacancy $vacancy): string
    {
        $submittedSlug = trim((string) $submittedSlug);
        $titleSlug = Str::slug($title);

        if ($submittedSlug === '' || $this->shouldRegenerateSlug($submittedSlug, $vacancy)) {
            $base = $titleSlug ?: 'vacancy';
        } else {
            $base = Str::slug($submittedSlug) ?: $titleSlug ?: 'vacancy';
        }

        $candidate = $base;
        $counter = 2;

        while (Vacancy::query()
            ->where('slug', $candidate)
            ->when($vacancy->exists, fn ($query) => $query->whereKeyNot($vacancy->getKey()))
            ->exists()) {
            $candidate = $base.'-'.$counter++;
        }

        return $candidate;
    }

    private function shouldRegenerateSlug(string $submittedSlug, Vacancy $vacancy): bool
    {
        return $vacancy->exists
            && $submittedSlug === (string) $vacancy->slug
            && $this->wasGeneratedFromTitle((string) $vacancy->slug, (string) $vacancy->title);
    }

    private function wasGeneratedFromTitle(string $slug, string $title): bool
    {
        $base = Str::slug($title);

        if ($base === '') {
            return false;
        }

        return $slug === $base || preg_match('/^'.preg_quote($base, '/').'-[0-9]+$/', $slug) === 1;
    }
}
