<?php

namespace App\Actions\Admin\Banners;

use App\Models\Cms\Banner;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DuplicateBanner
{
    public function handle(Banner $banner, ?Authenticatable $actor = null): Banner
    {
        return DB::transaction(function () use ($banner, $actor): Banner {
            $banner->load(['categories', 'translations']);

            $copy = $banner->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at', 'deleted_at']);
            $copy->title = ($banner->title ?: __('Banner')).' copy';
            $copy->status = 'draft';
            $copy->image_path = $this->duplicateImage($banner->image_path);
            $copy->created_by = $actor?->getAuthIdentifier();
            $copy->updated_by = $actor?->getAuthIdentifier();
            $copy->save();

            $copy->categories()->sync(
                $banner->categories
                    ->mapWithKeys(fn ($category, int $index): array => [$category->id => ['sort_order' => $index + 1]])
                    ->all()
            );

            foreach ($banner->translations as $translation) {
                $newTranslation = $translation->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at']);
                $newTranslation->banner_id = $copy->id;
                $newTranslation->title = ($translation->title ?: __('Banner')).' copy';
                $newTranslation->created_by = $actor?->getAuthIdentifier();
                $newTranslation->updated_by = $actor?->getAuthIdentifier();
                $newTranslation->save();
            }

            return $copy->refresh();
        });
    }

    private function duplicateImage(?string $imagePath): ?string
    {
        if (! $imagePath || ! Str::startsWith($imagePath, 'storage/')) {
            return $imagePath;
        }

        $source = Str::after($imagePath, 'storage/');

        if (! Storage::disk('public')->exists($source)) {
            return $imagePath;
        }

        $extension = pathinfo($source, PATHINFO_EXTENSION) ?: 'bin';
        $target = 'admin/uploads/banner/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->putFileAs(
            dirname($target),
            new File(Storage::disk('public')->path($source)),
            basename($target),
        );

        return 'storage/'.$target;
    }
}
