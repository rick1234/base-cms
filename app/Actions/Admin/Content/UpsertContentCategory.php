<?php

namespace App\Actions\Admin\Content;

use App\Models\User;
use App\Models\Cms\ContentCategory;
use App\Support\Admin\Content\ContentMediaManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UpsertContentCategory
{
    public function __construct(private readonly ContentMediaManager $mediaManager) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function handle(array $data, ?User $user, ?ContentCategory $category = null, array $images = []): ContentCategory
    {
        $category ??= new ContentCategory;
        $creating = ! $category->exists;
        $parentId = $data['parent_id'] ?? null;

        $category->fill([
            'parent_id' => $parentId ?: null,
            'name' => $data['name'],
            'slug' => $this->slug($data['slug'] ?? null, $data['name'], $category->id),
            'custom_url' => $data['custom_url'] ?? null,
            'description' => $data['description'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'image_path' => $data['image_path'] ?? $category->image_path,
            'slider_category_id' => $data['slider_category_id'] ?? null,
            'status' => $data['status'] ?? 'active',
            'is_hidden_from_navigation' => (bool) ($data['is_hidden_from_navigation'] ?? false),
            'sort_order' => $data['sort_order'] ?? $category->sort_order ?: $this->nextSortOrder($parentId),
            'updated_by' => $user?->id,
        ]);

        if ($creating) {
            $category->created_by = $user?->id;
        }

        $category->save();

        $captions = $data['image_captions'] ?? [];

        foreach ($images as $index => $image) {
            if ($image instanceof UploadedFile) {
                $this->mediaManager->storeCategoryImage($category, $image, $captions[$index] ?? null, $user);
            }
        }

        return $category;
    }

    private function nextSortOrder(mixed $parentId): int
    {
        return (int) ContentCategory::query()
            ->where('parent_id', $parentId ?: null)
            ->max('sort_order') + 1;
    }

    private function slug(?string $slug, string $name, ?int $ignoreId): string
    {
        $base = Str::slug($slug ?: $name) ?: 'category';
        $candidate = $base;
        $counter = 2;

        while (ContentCategory::query()
            ->where('slug', $candidate)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $candidate = $base.'-'.$counter++;
        }

        return $candidate;
    }
}
