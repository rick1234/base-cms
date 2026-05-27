<?php

namespace App\Actions\Admin\Content;

use App\Models\User;
use App\Models\Cms\ContentItem;
use Illuminate\Support\Str;

class DuplicateContentItem
{
    public function handle(ContentItem $contentItem, ?User $user): ContentItem
    {
        $copy = $contentItem->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at', 'deleted_at']);
        $copy->title = $contentItem->title.' - '.__('kopie').' - '.now()->format('d-m-Y H:i:s');
        $copy->slug = $this->slug($contentItem->slug ?: $contentItem->title);
        $copy->status = 'draft';
        $copy->structured_blocks = $this->cloneStructuredBlocks($contentItem->structured_blocks ?? []);
        $copy->created_by = $user?->id;
        $copy->updated_by = $user?->id;
        $copy->save();

        $sync = [];

        foreach ($contentItem->categories as $category) {
            $sync[$category->id] = ['sort_order' => $category->pivot->sort_order ?? 0];
        }

        $copy->categories()->sync($sync);

        foreach ($contentItem->attachments as $attachment) {
            $newAttachment = $attachment->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at', 'deleted_at']);
            $newAttachment->content_item_id = $copy->id;
            $newAttachment->created_by = $user?->id;
            $newAttachment->updated_by = $user?->id;
            $newAttachment->save();
        }

        foreach ($contentItem->images as $image) {
            $newImage = $image->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at', 'deleted_at']);
            $newImage->content_item_id = $copy->id;
            $newImage->created_by = $user?->id;
            $newImage->updated_by = $user?->id;
            $newImage->save();
        }

        return $copy;
    }

    private function slug(string $source): string
    {
        $base = Str::slug($source) ?: 'content';
        $candidate = $base.'-copy';
        $counter = 2;

        while (ContentItem::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-copy-'.$counter++;
        }

        return $candidate;
    }

    /**
     * @param  array<int, mixed>  $blocks
     * @return array<int, mixed>
     */
    private function cloneStructuredBlocks(array $blocks): array
    {
        return collect($blocks)
            ->map(function (mixed $block): mixed {
                if (! is_array($block)) {
                    return $block;
                }

                $block['uuid'] = (string) Str::uuid();

                return $block;
            })
            ->values()
            ->all();
    }
}
