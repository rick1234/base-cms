<?php

namespace App\Livewire\Admin\Events;

use App\Livewire\Admin\Media\ImageAlbum;
use App\Models\Cms\Event;
use App\Models\Cms\EventImage;
use App\Support\Admin\Events\EventMediaManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class EventImageAlbum extends ImageAlbum
{
    public Event $event;

    public function mount(Event $event): void
    {
        $this->event = $event;
        $this->syncImageForms();
    }

    protected function owner(): Model
    {
        return $this->event;
    }

    protected function imageModelClass(): string
    {
        return EventImage::class;
    }

    protected function mediaManagerClass(): string
    {
        return EventMediaManager::class;
    }

    protected function storeUploadedImage(object $mediaManager, UploadedFile $upload, string $defaultText): void
    {
        $mediaManager->storeImage($this->event, $upload, $defaultText, auth()->user(), [
            'alt_text' => $defaultText,
            'title_text' => $defaultText,
        ]);
    }

    protected function uploadRoute(): string
    {
        return route(
            request()->routeIs('cms.*') ? 'cms.events.image.upload' : 'admin.events.image.upload',
            ['id' => $this->event->id],
        );
    }

    protected function albumClass(): string
    {
        return 'event-image-album';
    }
}
