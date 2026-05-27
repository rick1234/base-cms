<?php

namespace App\Actions\Admin\Content;

use App\Models\User;
use App\Models\Cms\ContentAttachment;
use App\Models\Cms\ContentItem;
use App\Support\Admin\Content\ContentMediaManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UpsertContentItem
{
    public function __construct(private readonly ContentMediaManager $mediaManager) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $attachments
     */
    public function handle(array $data, ?User $user, ?ContentItem $contentItem = null, array $attachments = []): ContentItem
    {
        $contentItem ??= new ContentItem;
        $creating = ! $contentItem->exists;

        $contentItem->fill([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'slug' => $this->slug($data['slug'] ?? null, $data['title'], $contentItem->id),
            'locale' => $data['locale'] ?? app()->getLocale(),
            'meta_description' => $data['meta_description'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'active_from' => $data['active_from'] ?? null,
            'active_until' => $data['active_until'] ?? null,
            'form_id' => $data['form_id'] ?? null,
            'slider_category_id' => $data['slider_category_id'] ?? null,
            'updated_by' => $user?->id,
        ]);

        if ($creating) {
            $contentItem->created_by = $user?->id;
        }

        $contentItem->save();

        $this->syncCategories($contentItem, $data['categories'] ?? []);
        $this->syncAttachments($contentItem, $data, $attachments, $user);

        return $contentItem;
    }

    /**
     * @param  array<int, mixed>  $categoryIds
     */
    private function syncCategories(ContentItem $contentItem, array $categoryIds): void
    {
        $sync = [];

        foreach (array_values(array_unique(array_filter($categoryIds))) as $index => $categoryId) {
            $sync[(int) $categoryId] = ['sort_order' => $index + 1];
        }

        $contentItem->categories()->sync($sync);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $attachments
     */
    private function syncAttachments(ContentItem $contentItem, array $data, array $attachments, ?User $user): void
    {
        foreach (($data['existing_attachments'] ?? []) as $id => $attachmentData) {
            $attachment = $contentItem->attachments()->whereKey($id)->first();

            if (! $attachment instanceof ContentAttachment) {
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
                $this->mediaManager->storeAttachment($contentItem, $file, $names[$index] ?? null, $user);
            }
        }
    }

    private function slug(?string $slug, string $title, ?int $ignoreId): string
    {
        $base = Str::slug($slug ?: $title) ?: 'content';
        $candidate = $base;
        $counter = 2;

        while (ContentItem::query()
            ->where('slug', $candidate)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $candidate = $base.'-'.$counter++;
        }

        return $candidate;
    }
}
