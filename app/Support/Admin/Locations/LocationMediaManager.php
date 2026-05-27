<?php

namespace App\Support\Admin\Locations;

use App\Models\Cms\Location;
use App\Models\Cms\LocationImage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LocationMediaManager
{
    public function storeImage(Location $location, UploadedFile $file, ?string $caption = null, ?Authenticatable $actor = null): LocationImage
    {
        $path = $file->storeAs(
            'admin/uploads/locations/images',
            (string) Str::uuid().'.'.($file->guessExtension() ?: $file->extension() ?: 'bin'),
            'public',
        );

        return LocationImage::query()->create([
            'location_id' => $location->id,
            'folder' => 'storage/admin/uploads/locations/images',
            'image_path' => 'storage/'.$path,
            'caption' => $caption ?: $file->getClientOriginalName(),
            'sort_order' => ($location->images()->max('sort_order') ?? 0) + 1,
            'created_by' => $actor?->getAuthIdentifier(),
            'updated_by' => $actor?->getAuthIdentifier(),
        ]);
    }

    public function deleteMedia(Model $media, ?Authenticatable $actor = null): void
    {
        $path = $media->getAttribute('image_path');

        if ($path && Str::startsWith($path, 'storage/')) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }

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
        foreach ($ids as $index => $id) {
            $modelClass::query()
                ->whereKey($id)
                ->update([
                    'sort_order' => $index + 1,
                    'updated_by' => $actor?->getAuthIdentifier(),
                ]);
        }
    }
}
