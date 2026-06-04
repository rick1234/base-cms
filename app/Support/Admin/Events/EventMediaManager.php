<?php

namespace App\Support\Admin\Events;

use App\Models\Cms\Event;
use App\Models\Cms\EventAttachment;
use App\Models\Cms\EventImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class EventMediaManager
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function storeImage(Event $event, UploadedFile $file, ?string $caption, ?User $user, array $attributes = []): EventImage
    {
        return EventImage::query()->create([
            'event_id' => $event->id,
            'folder' => 'storage/events/images/',
            'image_path' => $this->store($file, 'events/images'),
            'caption' => $caption,
            'alt_text' => $attributes['alt_text'] ?? null,
            'title_text' => $attributes['title_text'] ?? null,
            'description' => $attributes['description'] ?? null,
            'credit' => $attributes['credit'] ?? null,
            'is_decorative' => (bool) ($attributes['is_decorative'] ?? false),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize() ?: null,
            'sort_order' => $this->nextSortOrder(EventImage::class, 'event_id', $event->id),
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);
    }

    public function storeAttachment(Event $event, UploadedFile $file, ?string $name, ?User $user): EventAttachment
    {
        return EventAttachment::query()->create([
            'event_id' => $event->id,
            'name' => $name ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'type' => $file->getClientMimeType(),
            'url' => $this->store($file, 'events/attachments'),
            'sort_order' => $this->nextSortOrder(EventAttachment::class, 'event_id', $event->id),
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<int>  $ids
     */
    public function updateSortOrder(string $modelClass, array $ids, ?User $user): void
    {
        foreach (array_values($ids) as $index => $id) {
            $modelClass::query()
                ->whereKey($id)
                ->update([
                    'sort_order' => $index + 1,
                    'updated_by' => $user?->id,
                    'updated_at' => now(),
                ]);
        }
    }

    public function deleteMedia(Model $media, ?User $user): void
    {
        if (array_key_exists('updated_by', $media->getAttributes())) {
            $media->setAttribute('updated_by', $user?->id);
            $media->save();
        }

        $media->delete();
    }

    private function store(UploadedFile $file, string $directory): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension();
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'upload';
        $fileName = $name.'-'.Str::random(8).($extension ? '.'.$extension : '');
        $path = $file->storeAs($directory, $fileName, 'public');

        return 'storage/'.str_replace('\\', '/', $path);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function nextSortOrder(string $modelClass, string $ownerColumn, int $ownerId): int
    {
        return (int) $modelClass::query()->where($ownerColumn, $ownerId)->max('sort_order') + 1;
    }
}
