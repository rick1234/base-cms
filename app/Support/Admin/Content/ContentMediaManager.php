<?php

namespace App\Support\Admin\Content;

use App\Models\User;
use App\Models\Cms\ContentAttachment;
use App\Models\Cms\ContentCategory;
use App\Models\Cms\ContentCategoryImage;
use App\Models\Cms\ContentImage;
use App\Models\Cms\ContentItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ContentMediaManager
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function storeItemImage(ContentItem $contentItem, UploadedFile $file, ?string $caption, ?User $user, array $attributes = []): ContentImage
    {
        return ContentImage::query()->create([
            'content_item_id' => $contentItem->id,
            'folder' => 'storage/content/images/',
            'image_path' => $this->store($file, 'content/images'),
            'caption' => $caption,
            'alt_text' => $attributes['alt_text'] ?? null,
            'title_text' => $attributes['title_text'] ?? null,
            'description' => $attributes['description'] ?? null,
            'credit' => $attributes['credit'] ?? null,
            'is_decorative' => (bool) ($attributes['is_decorative'] ?? false),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize() ?: null,
            'sort_order' => $this->nextSortOrder(ContentImage::class, 'content_item_id', $contentItem->id),
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);
    }

    public function storeCategoryImage(ContentCategory $category, UploadedFile $file, ?string $caption, ?User $user): ContentCategoryImage
    {
        return ContentCategoryImage::query()->create([
            'content_category_id' => $category->id,
            'folder' => 'storage/content/category-images/',
            'image_path' => $this->store($file, 'content/category-images'),
            'caption' => $caption,
            'sort_order' => $this->nextSortOrder(ContentCategoryImage::class, 'content_category_id', $category->id),
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);
    }

    public function storeAttachment(ContentItem $contentItem, UploadedFile $file, ?string $name, ?User $user): ContentAttachment
    {
        return ContentAttachment::query()->create([
            'content_item_id' => $contentItem->id,
            'name' => $name ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'type' => $file->getClientMimeType(),
            'url' => $this->store($file, 'content/attachments'),
            'sort_order' => $this->nextSortOrder(ContentAttachment::class, 'content_item_id', $contentItem->id),
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);
    }

    public function storeBlockImage(UploadedFile $file): string
    {
        return $this->store($file, 'content/blocks');
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<int>  $ids
     */
    public function updateSortOrder(string $modelClass, array $ids, ?User $user): void
    {
        foreach (array_values($ids) as $index => $id) {
            $modelClass::query()
                ->whereKey($id)
                ->update([
                    'sort_order' => $index + 1,
                    'updated_by' => $user?->id,
                    'updated_at' => now(),
                ]);
        }
    }

    public function deleteMedia(Model $media, ?User $user): void
    {
        if ($media->isFillable('updated_by') || array_key_exists('updated_by', $media->getAttributes())) {
            $media->setAttribute('updated_by', $user?->id);
            $media->save();
        }

        $media->delete();
    }

    private function store(UploadedFile $file, string $directory): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension();
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'upload';
        $fileName = $name.'-'.Str::random(8).($extension ? '.'.$extension : '');
        $path = $file->storeAs($directory, $fileName, 'public');

        return 'storage/'.str_replace('\\', '/', $path);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function nextSortOrder(string $modelClass, string $ownerColumn, int $ownerId): int
    {
        return (int) $modelClass::query()->where($ownerColumn, $ownerId)->max('sort_order') + 1;
    }
}
