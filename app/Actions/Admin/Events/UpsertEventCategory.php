<?php

namespace App\Actions\Admin\Events;

use App\Models\User;
use App\Models\Cms\EventCategory;
use Illuminate\Support\Str;

class UpsertEventCategory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?User $user, ?EventCategory $category = null): EventCategory
    {
        $category ??= new EventCategory;
        $creating = ! $category->exists;
        $parentId = $data['parent_id'] ?? null;

        $category->fill([
            'parent_id' => $parentId ?: null,
            'name' => $data['name'],
            'slug' => $this->slug($data['slug'] ?? null, $data['name'], $category->id),
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
            'sort_order' => $data['sort_order'] ?? $category->sort_order ?: $this->nextSortOrder($parentId),
            'updated_by' => $user?->id,
        ]);

        if ($creating) {
            $category->created_by = $user?->id;
        }

        $category->save();

        return $category;
    }

    private function nextSortOrder(mixed $parentId): int
    {
        return (int) EventCategory::query()
            ->where('parent_id', $parentId ?: null)
            ->max('sort_order') + 1;
    }

    private function slug(?string $slug, string $name, ?int $ignoreId): string
    {
        $base = Str::slug($slug ?: $name) ?: 'event-category';
        $candidate = $base;
        $counter = 2;

        while (EventCategory::query()
            ->where('slug', $candidate)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $candidate = $base.'-'.$counter++;
        }

        return $candidate;
    }
}
