<?php

namespace App\Actions\Admin\Banners;

use App\Models\Cms\Banner;
use App\Models\Cms\BannerTranslation;
use App\Support\Admin\Banners\BannerMediaManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class UpsertBanner
{
    public function __construct(private readonly BannerMediaManager $mediaManager) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?Authenticatable $actor = null, ?Banner $banner = null, ?UploadedFile $image = null): Banner
    {
        $banner ??= new Banner;

        $attributes = Arr::only($data, [
            'title',
            'link_url',
            'button_text',
            'text',
            'status',
            'starts_at',
            'ends_at',
            'sort_order',
        ]);

        $attributes['metadata'] = [
            'alt_text' => $data['alt_text'] ?? null,
            'target' => $data['target'] ?? null,
        ];

        if (! $banner->exists) {
            $attributes['created_by'] = $actor?->getAuthIdentifier();
        }

        $attributes['updated_by'] = $actor?->getAuthIdentifier();

        $banner->fill($attributes)->save();

        if ($image instanceof UploadedFile) {
            $this->mediaManager->replaceImage($banner, $image, $actor);
        }

        if ((bool) ($data['delete_image'] ?? false)) {
            $this->mediaManager->deleteImage($banner, $actor);
        }

        $this->syncCategories($banner, (array) ($data['categories'] ?? []));
        $this->syncTranslations($banner, (array) ($data['translations'] ?? []), $actor);

        return $banner->refresh();
    }

    /**
     * @param  array<int|string, mixed>  $categoryIds
     */
    private function syncCategories(Banner $banner, array $categoryIds): void
    {
        $ids = collect($categoryIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $banner->categories()->sync(
            $ids->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index + 1]])->all()
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    private function syncTranslations(Banner $banner, array $translations, ?Authenticatable $actor): void
    {
        foreach ($translations as $locale => $row) {
            if (blank($row['title'] ?? null) && blank($row['subtitle'] ?? null) && blank($row['link_url'] ?? null) && blank($row['button_text'] ?? null) && blank($row['content'] ?? null)) {
                $banner->translations()->where('locale', $locale)->delete();

                continue;
            }

            $translation = $banner->translations()->firstOrNew(['locale' => $locale]);

            if (! $translation->exists) {
                $translation->created_by = $actor?->getAuthIdentifier();
            }

            $translation->fill([
                'title' => $row['title'] ?? null,
                'subtitle' => $row['subtitle'] ?? null,
                'link_url' => $row['link_url'] ?? null,
                'button_text' => $row['button_text'] ?? null,
                'content' => $row['content'] ?? null,
                'metadata' => [
                    'alt_text' => $row['alt_text'] ?? null,
                ],
                'updated_by' => $actor?->getAuthIdentifier(),
            ])->save();
        }

        $primaryTranslation = $banner->translations()->where('locale', app()->getLocale())->first()
            ?: $banner->translations()->first();

        if ($primaryTranslation instanceof BannerTranslation) {
            $banner->fill([
                'title' => $primaryTranslation->title ?: $banner->title,
                'link_url' => $primaryTranslation->link_url ?: $banner->link_url,
                'button_text' => $primaryTranslation->button_text ?: $banner->button_text,
                'text' => $primaryTranslation->content ?: $banner->text,
                'updated_by' => $actor?->getAuthIdentifier(),
            ])->save();
        }
    }
}
