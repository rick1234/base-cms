<?php

namespace App\Actions\Admin\Faq;

use App\Models\Cms\FaqAttachment;
use App\Models\Cms\FaqItem;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UpsertFaqItem
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $attachments
     */
    public function handle(array $data, ?Authenticatable $actor = null, ?FaqItem $faqItem = null, array $attachments = []): FaqItem
    {
        $faqItem ??= new FaqItem;
        $categoryIds = collect($data['categories'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $attributes = Arr::only($data, [
            'question',
            'slug',
            'locale',
            'intro',
            'body',
            'meta_description',
            'status',
            'active_from',
            'active_until',
            'sort_order',
        ]);

        if (blank($attributes['slug'] ?? null)) {
            $attributes['slug'] = Str::slug((string) $attributes['question']);
        }

        if (! $faqItem->exists) {
            $attributes['created_by'] = $actor?->getAuthIdentifier();
        }

        $attributes['updated_by'] = $actor?->getAuthIdentifier();

        $faqItem->fill($attributes)->save();

        $faqItem->categories()->sync(
            $categoryIds->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index + 1]])->all()
        );

        $this->storeAttachments($faqItem, $attachments, (array) ($data['attachment_names'] ?? []), $actor);
        $this->updateExistingAttachments($faqItem, (array) ($data['existing_attachments'] ?? []), $actor);

        return $faqItem->refresh();
    }

    /**
     * @param  array<int, UploadedFile>  $attachments
     * @param  array<int|string, string|null>  $names
     */
    private function storeAttachments(FaqItem $faqItem, array $attachments, array $names, ?Authenticatable $actor): void
    {
        foreach ($attachments as $index => $file) {
            $path = $file->storeAs(
                'admin/uploads/faq/attachments',
                (string) Str::uuid().'.'.($file->guessExtension() ?: $file->extension() ?: 'bin'),
                'public',
            );

            FaqAttachment::query()->create([
                'faq_item_id' => $faqItem->id,
                'name' => filled($names[$index] ?? null) ? $names[$index] : $file->getClientOriginalName(),
                'type' => $file->getClientMimeType(),
                'url' => 'storage/'.$path,
                'sort_order' => ($faqItem->attachments()->max('sort_order') ?? 0) + 1,
                'created_by' => $actor?->getAuthIdentifier(),
                'updated_by' => $actor?->getAuthIdentifier(),
            ]);
        }
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $attachments
     */
    private function updateExistingAttachments(FaqItem $faqItem, array $attachments, ?Authenticatable $actor): void
    {
        foreach ($attachments as $id => $attachmentData) {
            $attachment = $faqItem->attachments()->whereKey((int) $id)->first();

            if (! $attachment) {
                continue;
            }

            if (! empty($attachmentData['delete'])) {
                $attachment->forceFill(['updated_by' => $actor?->getAuthIdentifier()])->save();
                $attachment->delete();

                continue;
            }

            $attachment->fill([
                'name' => $attachmentData['name'] ?? $attachment->name,
                'sort_order' => (int) ($attachmentData['sort_order'] ?? $attachment->sort_order),
                'updated_by' => $actor?->getAuthIdentifier(),
            ])->save();
        }
    }
}
