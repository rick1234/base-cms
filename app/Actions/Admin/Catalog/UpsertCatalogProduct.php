<?php

namespace App\Actions\Admin\Catalog;

use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogProductAttachment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UpsertCatalogProduct
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $attachments
     */
    public function handle(array $data, ?Authenticatable $actor = null, ?CatalogProduct $product = null, array $attachments = []): CatalogProduct
    {
        $product ??= new CatalogProduct;
        $categoryIds = collect($data['categories'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $attributes = Arr::only($data, [
            'sku',
            'name',
            'description',
            'meta_title',
            'meta_description',
            'brand_id',
            'status',
            'active_from',
            'active_until',
        ]);
        $attributes['price'] = $this->moneyToCents($data['price'] ?? 0);

        foreach (['brand_id'] as $nullableInteger) {
            if (blank($attributes[$nullableInteger] ?? null)) {
                $attributes[$nullableInteger] = null;
            }
        }

        if (! $product->exists) {
            $attributes['created_by'] = $actor?->getAuthIdentifier();
        }

        $attributes['updated_by'] = $actor?->getAuthIdentifier();

        $product->fill($attributes)->save();

        $product->categories()->sync(
            $categoryIds->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index + 1]])->all()
        );

        $this->storeAttachments($product, $attachments, (array) ($data['attachment_names'] ?? []), $actor);
        $this->updateExistingAttachments($product, (array) ($data['existing_attachments'] ?? []), $actor);

        return $product->refresh();
    }

    /**
     * @param  array<int, UploadedFile>  $attachments
     * @param  array<int|string, string|null>  $names
     */
    private function storeAttachments(CatalogProduct $product, array $attachments, array $names, ?Authenticatable $actor): void
    {
        foreach ($attachments as $index => $file) {
            $path = $file->storeAs(
                'admin/uploads/catalog/attachments',
                (string) Str::uuid().'.'.($file->guessExtension() ?: $file->extension() ?: 'bin'),
                'public',
            );

            CatalogProductAttachment::query()->create([
                'catalog_product_id' => $product->id,
                'name' => filled($names[$index] ?? null) ? $names[$index] : $file->getClientOriginalName(),
                'type' => $file->getClientMimeType(),
                'url' => 'storage/'.$path,
                'sort_order' => ($product->attachments()->max('sort_order') ?? 0) + 1,
                'created_by' => $actor?->getAuthIdentifier(),
                'updated_by' => $actor?->getAuthIdentifier(),
            ]);
        }
    }

    private function moneyToCents(mixed $value): int
    {
        $normalized = str_replace(',', '.', (string) $value);
        $normalized = preg_replace('/[^0-9.-]/', '', $normalized) ?: '0';

        return (int) round(((float) $normalized) * 100);
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $attachments
     */
    private function updateExistingAttachments(CatalogProduct $product, array $attachments, ?Authenticatable $actor): void
    {
        foreach ($attachments as $id => $attachmentData) {
            $attachment = $product->attachments()->whereKey((int) $id)->first();

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
