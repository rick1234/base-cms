<?php

namespace App\Actions\Admin\Events;

use App\Models\User;
use App\Models\Cms\Event;
use App\Models\Cms\EventAttachment;
use App\Models\Cms\EventPart;
use App\Support\Admin\Events\EventMediaManager;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UpsertEvent
{
    public function __construct(private readonly EventMediaManager $mediaManager) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $attachments
     */
    public function handle(array $data, ?User $user, ?Event $event = null, array $attachments = [], ?UploadedFile $image = null): Event
    {
        $event ??= new Event;
        $creating = ! $event->exists;

        $event->fill([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'slug' => $this->slug($data['slug'] ?? null, $data['title'], $event->id),
            'locale' => $data['locale'] ?? app()->getLocale(),
            'intro' => $data['intro'] ?? null,
            'body' => $data['body'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'active_from' => $data['active_from'] ?? null,
            'active_until' => $data['active_until'] ?? null,
            'form_id' => $data['form_id'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'updated_by' => $user?->id,
        ]);

        if ($creating) {
            $event->created_by = $user?->id;
        }

        $event->save();

        $this->syncCategories($event, $data['categories'] ?? []);
        $this->syncAttachments($event, $data, $attachments, $user);
        $this->syncParts($event, $data, $user);

        if ($image instanceof UploadedFile) {
            $this->mediaManager->storeImage($event, $image, $data['image_caption'] ?? null, $user);
        }

        return $event;
    }

    /**
     * @param  array<int, mixed>  $categoryIds
     */
    private function syncCategories(Event $event, array $categoryIds): void
    {
        $sync = [];

        foreach (array_values(array_unique(array_filter($categoryIds))) as $index => $categoryId) {
            $sync[(int) $categoryId] = ['sort_order' => $index + 1];
        }

        $event->categories()->sync($sync);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $attachments
     */
    private function syncAttachments(Event $event, array $data, array $attachments, ?User $user): void
    {
        foreach (($data['existing_attachments'] ?? []) as $id => $attachmentData) {
            $attachment = $event->attachments()->whereKey($id)->first();

            if (! $attachment instanceof EventAttachment) {
                continue;
            }

            if ((bool) ($attachmentData['delete'] ?? false)) {
                $attachment->delete();

                continue;
            }

            $attachment->fill([
                'name' => $attachmentData['name'] ?? $attachment->name,
                'sort_order' => $attachmentData['sort_order'] ?? $attachment->sort_order,
                'updated_by' => $user?->id,
            ])->save();
        }

        $names = $data['attachment_names'] ?? [];

        foreach ($attachments as $index => $file) {
            if ($file instanceof UploadedFile) {
                $this->mediaManager->storeAttachment($event, $file, $names[$index] ?? null, $user);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncParts(Event $event, array $data, ?User $user): void
    {
        foreach (($data['existing_parts'] ?? []) as $id => $partData) {
            $part = $event->parts()->whereKey($id)->first();

            if (! $part instanceof EventPart) {
                continue;
            }

            if ((bool) ($partData['delete'] ?? false)) {
                $part->delete();

                continue;
            }

            $startsAt = $this->dateTime($partData['date'] ?? null, $partData['starts_at'] ?? null);

            $part->fill([
                'title' => $partData['title'] ?? $part->title,
                'starts_at' => $startsAt,
                'ends_at' => $this->dateTime($partData['date'] ?? null, $partData['ends_at'] ?? null),
                'sort_order' => $partData['sort_order'] ?? $part->sort_order,
                'updated_by' => $user?->id,
            ])->save();
        }

        foreach (($data['new_parts'] ?? []) as $index => $partData) {
            if (blank($partData['title'] ?? null)) {
                continue;
            }

            EventPart::query()->create([
                'event_id' => $event->id,
                'title' => $partData['title'],
                'starts_at' => $this->dateTime($partData['date'] ?? null, $partData['starts_at'] ?? null),
                'ends_at' => $this->dateTime($partData['date'] ?? null, $partData['ends_at'] ?? null),
                'sort_order' => (int) ($partData['sort_order'] ?? $index + 1),
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);
        }
    }

    private function dateTime(?string $date, ?string $time): ?Carbon
    {
        if (blank($date) && blank($time)) {
            return null;
        }

        $date = $date ?: now()->toDateString();
        $time = $time ?: '00:00';

        return Carbon::parse($date.' '.$time);
    }

    private function slug(?string $slug, string $title, ?int $ignoreId): string
    {
        $base = Str::slug($slug ?: $title) ?: 'event';
        $candidate = $base;
        $counter = 2;

        while (Event::query()
            ->where('slug', $candidate)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $candidate = $base.'-'.$counter++;
        }

        return $candidate;
    }
}
