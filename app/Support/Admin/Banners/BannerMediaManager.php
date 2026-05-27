<?php

namespace App\Support\Admin\Banners;

use App\Models\Cms\Banner;
use Illuminate\Contracts\Auth\Authenticatable;
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

        $banner->fill([
            'image_path' => $this->storeImage($file),
            'updated_by' => $actor?->getAuthIdentifier(),
        ])->save();
    }

    public function deleteImage(Banner $banner, ?Authenticatable $actor = null): void
    {
        $this->deleteImageFile($banner->image_path);

        $banner->fill([
            'image_path' => null,
            'updated_by' => $actor?->getAuthIdentifier(),
        ])->save();
    }

    private function deleteImageFile(?string $path): void
    {
        if ($path && Str::startsWith($path, 'storage/')) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }
    }
}
