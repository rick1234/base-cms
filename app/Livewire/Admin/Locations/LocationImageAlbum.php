<?php

namespace App\Livewire\Admin\Locations;

use App\Livewire\Admin\Media\ImageAlbum;
use App\Models\Cms\Location;
use App\Models\Cms\LocationImage;
use App\Support\Admin\Locations\LocationMediaManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class LocationImageAlbum extends ImageAlbum
{
    public Location $location;

    public function mount(Location $location): void
    {
        $this->location = $location;
        $this->syncImageForms();
    }

    protected function owner(): Model
    {
        return $this->location;
    }

    protected function imageModelClass(): string
    {
        return LocationImage::class;
    }

    protected function mediaManagerClass(): string
    {
        return LocationMediaManager::class;
    }

    protected function storeUploadedImage(object $mediaManager, UploadedFile $upload, string $defaultText): void
    {
        $mediaManager->storeImage($this->location, $upload, $defaultText, auth()->user(), [
            'alt_text' => $defaultText,
            'title_text' => $defaultText,
        ]);
    }

    protected function uploadRoute(): string
    {
        return route(
            request()->routeIs('cms.*') ? 'cms.locations.image.upload' : 'admin.locations.image.upload',
            ['id' => $this->location->id],
        );
    }

    protected function albumClass(): string
    {
        return 'location-image-album';
    }
}
