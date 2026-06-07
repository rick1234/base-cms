<?php

namespace App\Support\Admin\Banners;

use App\Models\Cms\Banner;
use App\Models\Cms\BannerImage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BannerMediaManager
{
    public function storeImage(UploadedFile $file): string
    {
        $path = $file->storeAs(
            'admin/uploads/banner',
            (string) Str::uuid().'.'.($file->guessExtension() ?: $file->extension() ?: 'bin'),
            'public',
        );

        return 'storage/'.$path;
    }

    public function replaceImage(Banner $banner, UploadedFile $file, ?Authenticatable $actor = null): void
    {
        $this->deleteImageFile($banner->image_path);

        $path = $this->storeImage($file);

        $banner->fill([
            'image_path' => $path,
            'updated_by' => $actor?->getAuthIdentifier(),
        ])->save();

        if (! $banner->images()->exists()) {
            $this->storeBannerImage($banner, $file, $banner->title ?: $file->getClientOriginalName(), $actor, [
                'image_path' => $path,
                'alt_text' => $banner->metadata['alt_text'] ?? null,
            ]);
        }
    }

    public function deleteImage(Banner $banner, ?Authenticatable $actor = null): void
    {
        $this->deleteImageFile($banner->image_path);

        $banner->fill([
            'image_path' => null,
            'updated_by' => $actor?->getAuthIdentifier(),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function storeBannerImage(Banner $banner, UploadedFile $file, ?string $caption = null, ?Authenticatable $actor = null, array $attributes = []): BannerImage
    {
        $path = $attributes['image_path'] ?? $this->storeImage($file);

        return BannerImage::query()->create([
            'banner_id' => $banner->id,
            'folder' => 'storage/admin/uploads/banner',
            'image_path' => $path,
            'caption' => $caption ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'alt_text' => $attributes['alt_text'] ?? null,
            'title_text' => $attributes['title_text'] ?? null,
            'description' => $attributes['description'] ?? null,
            'credit' => $attributes['credit'] ?? null,
            'is_decorative' => (bool) ($attributes['is_decorative'] ?? false),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize() ?: null,
            'sort_order' => ($banner->images()->max('sort_order') ?? 0) + 1,
            'created_by' => $actor?->getAuthIdentifier(),
            'updated_by' => $actor?->getAuthIdentifier(),
        ]);
    }

    public function deleteMedia(Model $media, ?Authenticatable $actor = null): void
    {
        $this->deleteImageFile($media->getAttribute('image_path'));

        $media->setAttribute('updated_by', $actor?->getAuthIdentifier());
        $media->save();
        $media->delete();
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<int>  $ids
     */
    public function updateSortOrder(string $modelClass, array $ids, ?Authenticatable $actor = null): void
    {
        foreach (array_values($ids) as $index => $id) {
            $modelClass::query()
                ->whereKey($id)
                ->update([
                    'sort_order' => $index + 1,
                    'updated_by' => $actor?->getAuthIdentifier(),
                    'updated_at' => now(),
                ]);
        }
    }

    private function deleteImageFile(?string $path): void
    {
        if ($path && Str::startsWith($path, 'storage/')) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }
    }
}
