<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Media\ImageAlbum;
use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogProductImage;
use App\Support\Admin\Catalog\CatalogMediaManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class CatalogProductImageAlbum extends ImageAlbum
{
    public CatalogProduct $product;

    public function mount(CatalogProduct $product): void
    {
        $this->product = $product;
        $this->syncImageForms();
    }

    protected function owner(): Model
    {
        return $this->product;
    }

    protected function imageModelClass(): string
    {
        return CatalogProductImage::class;
    }

    protected function mediaManagerClass(): string
    {
        return CatalogMediaManager::class;
    }

    protected function storeUploadedImage(object $mediaManager, UploadedFile $upload, string $defaultText): void
    {
        $mediaManager->storeImage($this->product, $upload, $defaultText, auth()->user(), [
            'alt_text' => $defaultText,
            'title_text' => $defaultText,
        ]);
    }

    protected function uploadRoute(): string
    {
        return route(
            request()->routeIs('cms.*') ? 'cms.catalog.images.upload' : 'admin.catalog.images.upload',
            ['id' => $this->product->id],
        );
    }
}
