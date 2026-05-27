<?php

namespace App\Actions\Admin\Locations;

use App\Models\Cms\Location;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UpsertLocation
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?Authenticatable $actor = null, ?Location $location = null): Location
    {
        $location ??= new Location;

        $attributes = Arr::only($data, [
            'name',
            'street_address',
            'postal_code',
            'city',
            'country_code',
            'email',
            'phone',
            'website_url',
            'chamber_of_commerce_number',
            'description',
            'latitude',
            'longitude',
            'map_info',
            'status',
            'active_from',
            'active_until',
            'sort_order',
        ]);

        $attributes['metadata'] = [
            'slug' => $data['slug'] ?? Str::slug((string) ($data['name'] ?? '')),
            'seo_title' => $data['seo_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ];

        if (! $location->exists) {
            $attributes['created_by'] = $actor?->getAuthIdentifier();
        }

        $attributes['updated_by'] = $actor?->getAuthIdentifier();

        $location->fill($attributes)->save();

        $this->syncCategories($location, (array) ($data['categories'] ?? []));

        return $location->refresh();
    }

    /**
     * @param  array<int|string, mixed>  $categoryIds
     */
    private function syncCategories(Location $location, array $categoryIds): void
    {
        $ids = collect($categoryIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $location->categories()->sync(
            $ids->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index + 1]])->all()
        );
    }
}
