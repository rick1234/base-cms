<?php

namespace App\Actions\Admin\Locations;

use App\Models\Cms\Location;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DuplicateLocation
{
    public function handle(Location $location, ?Authenticatable $actor = null): Location
    {
        return DB::transaction(function () use ($location, $actor): Location {
            $location->load(['categories', 'images', 'openingHours', 'specialOpeningHours']);

            $copy = $location->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at', 'deleted_at']);
            $copy->name = ($location->name ?: __('Location')).' - '.__('copy').' - '.now()->format('d-m-Y H:i:s');
            $copy->created_by = $actor?->getAuthIdentifier();
            $copy->updated_by = $actor?->getAuthIdentifier();
            $copy->save();

            $copy->categories()->sync(
                $location->categories
                    ->mapWithKeys(fn ($category, int $index): array => [$category->id => ['sort_order' => $index + 1]])
                    ->all()
            );

            foreach ($location->images as $image) {
                $newImage = $image->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at', 'deleted_at']);
                $newImage->location_id = $copy->id;
                $newImage->image_path = $this->duplicateImage($image->image_path);
                $newImage->created_by = $actor?->getAuthIdentifier();
                $newImage->updated_by = $actor?->getAuthIdentifier();
                $newImage->save();
            }

            foreach ($location->openingHours as $openingHour) {
                $newOpeningHour = $openingHour->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at']);
                $newOpeningHour->location_id = $copy->id;
                $newOpeningHour->created_by = $actor?->getAuthIdentifier();
                $newOpeningHour->updated_by = $actor?->getAuthIdentifier();
                $newOpeningHour->save();
            }

            foreach ($location->specialOpeningHours as $specialOpeningHour) {
                $newSpecialOpeningHour = $specialOpeningHour->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at']);
                $newSpecialOpeningHour->location_id = $copy->id;
                $newSpecialOpeningHour->created_by = $actor?->getAuthIdentifier();
                $newSpecialOpeningHour->updated_by = $actor?->getAuthIdentifier();
                $newSpecialOpeningHour->save();
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
        $target = 'admin/uploads/locations/images/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->putFileAs(
            dirname($target),
            new File(Storage::disk('public')->path($source)),
            basename($target),
        );

        return 'storage/'.$target;
    }
}
