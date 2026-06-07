<?php

namespace App\Livewire\Admin\Banners;

use App\Livewire\Admin\Media\ImageAlbum;
use App\Models\Cms\Banner;
use App\Models\Cms\BannerImage;
use App\Support\Admin\Banners\BannerMediaManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class BannerImageAlbum extends ImageAlbum
{
    public Banner $banner;

    public function mount(Banner $banner): void
    {
        $this->banner = $banner;
        $this->syncImageForms();
    }

    protected function owner(): Model
    {
        return $this->banner;
    }

    protected function imageModelClass(): string
    {
        return BannerImage::class;
    }

    protected function mediaManagerClass(): string
    {
        return BannerMediaManager::class;
    }

    protected function storeUploadedImage(object $mediaManager, UploadedFile $upload, string $defaultText): void
    {
        $mediaManager->storeBannerImage($this->banner, $upload, $defaultText, auth()->user(), [
            'alt_text' => $defaultText,
            'title_text' => $defaultText,
        ]);
    }

    protected function uploadRoute(): string
    {
        return route(
            request()->routeIs('cms.*') ? 'cms.banners.images.upload' : 'admin.banners.images.upload',
            ['id' => $this->banner->id],
        );
    }

    protected function albumClass(): string
    {
        return 'banner-image-album';
    }
}
