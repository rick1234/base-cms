<?php

namespace App\Actions\Admin\Events;

use App\Models\User;
use App\Models\Cms\Event;
use Illuminate\Support\Str;

class DuplicateEvent
{
    public function handle(Event $event, ?User $user): Event
    {
        $copy = $event->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at', 'deleted_at']);
        $copy->title = $event->title.' - '.__('kopie').' - '.now()->format('d-m-Y H:i:s');
        $copy->slug = $this->slug($event->slug ?: $event->title);
        $copy->status = 'draft';
        $copy->created_by = $user?->id;
        $copy->updated_by = $user?->id;
        $copy->save();

        $sync = [];

        foreach ($event->categories as $category) {
            $sync[$category->id] = ['sort_order' => $category->pivot->sort_order ?? 0];
        }

        $copy->categories()->sync($sync);

        foreach ($event->attachments as $attachment) {
            $newAttachment = $attachment->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at', 'deleted_at']);
            $newAttachment->event_id = $copy->id;
            $newAttachment->created_by = $user?->id;
            $newAttachment->updated_by = $user?->id;
            $newAttachment->save();
        }

        foreach ($event->images as $image) {
            $newImage = $image->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at', 'deleted_at']);
            $newImage->event_id = $copy->id;
            $newImage->created_by = $user?->id;
            $newImage->updated_by = $user?->id;
            $newImage->save();
        }

        foreach ($event->parts as $part) {
            $newPart = $part->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at']);
            $newPart->event_id = $copy->id;
            $newPart->created_by = $user?->id;
            $newPart->updated_by = $user?->id;
            $newPart->save();
        }

        return $copy;
    }

    private function slug(string $source): string
    {
        $base = Str::slug($source) ?: 'event';
        $candidate = $base.'-copy';
        $counter = 2;

        while (Event::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-copy-'.$counter++;
        }

        return $candidate;
    }
}
