<?php

namespace App\Actions\Admin\Faq;

use App\Models\Cms\FaqItem;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UpsertFaqItem
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?Authenticatable $actor = null, ?FaqItem $faqItem = null): FaqItem
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

        $attributes['locale'] ??= app()->getLocale();
        $attributes['intro'] = null;
        $attributes['meta_description'] = null;
        $attributes['active_from'] = null;
        $attributes['active_until'] = null;

        if (blank($attributes['slug'] ?? null)) {
            $attributes['slug'] = Str::slug((string) $attributes['question']);
        }

        $attributes['metadata'] = $this->metadataFor($faqItem, $data);

        if (! $faqItem->exists) {
            $attributes['created_by'] = $actor?->getAuthIdentifier();
        }

        $attributes['updated_by'] = $actor?->getAuthIdentifier();

        $faqItem->fill($attributes)->save();

        $faqItem->categories()->sync(
            $categoryIds->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index + 1]])->all()
        );

        return $faqItem->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function metadataFor(FaqItem $faqItem, array $data): ?array
    {
        $metadata = (array) ($faqItem->metadata ?? []);
        $moreInfoLinks = $this->moreInfoLinks($data);

        if ($moreInfoLinks === []) {
            unset($metadata['more_info'], $metadata['more_info_links']);

            return $metadata === [] ? null : $metadata;
        }

        $metadata['more_info_links'] = $moreInfoLinks;
        $metadata['more_info'] = $moreInfoLinks[0];

        return $metadata;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{navigation_item_id: int, label?: string}>
     */
    private function moreInfoLinks(array $data): array
    {
        $links = is_array($data['more_info_links'] ?? null) ? $data['more_info_links'] : [];

        if ($links === [] && (filled($data['more_info_navigation_item_id'] ?? null) || filled($data['more_info_label'] ?? null))) {
            $links[] = [
                'navigation_item_id' => $data['more_info_navigation_item_id'] ?? null,
                'label' => $data['more_info_label'] ?? null,
            ];
        }

        return collect($links)
            ->filter(fn (mixed $link): bool => is_array($link) && filled($link['navigation_item_id'] ?? null))
            ->map(function (array $link): array {
                $normalized = [
                    'navigation_item_id' => (int) $link['navigation_item_id'],
                ];

                if (filled($link['label'] ?? null)) {
                    $normalized['label'] = trim((string) $link['label']);
                }

                return $normalized;
            })
            ->unique(fn (array $link): int => $link['navigation_item_id'])
            ->values()
            ->all();
    }
}
