<?php

namespace App\Livewire\Admin\Content;

use App\Livewire\Admin\Media\ImageAlbum;
use App\Models\Cms\ContentImage;
use App\Models\Cms\ContentItem;
use App\Support\Admin\Content\ContentMediaManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class ContentImageAlbum extends ImageAlbum
{
    public ContentItem $contentItem;

    public function mount(ContentItem $contentItem): void
    {
        $this->contentItem = $contentItem;
        $this->syncImageForms();
    }

    protected function owner(): Model
    {
        return $this->contentItem;
    }

    protected function imageModelClass(): string
    {
        return ContentImage::class;
    }

    protected function mediaManagerClass(): string
    {
        return ContentMediaManager::class;
    }

    protected function storeUploadedImage(object $mediaManager, UploadedFile $upload, string $defaultText): void
    {
        $mediaManager->storeItemImage($this->contentItem, $upload, $defaultText, auth()->user(), [
            'alt_text' => $defaultText,
            'title_text' => $defaultText,
        ]);
    }

    protected function uploadRoute(): string
    {
        return route(
            request()->routeIs('cms.*') ? 'cms.content.images.upload' : 'admin.content.images.upload',
            ['id' => $this->contentItem->id],
        );
    }
}
