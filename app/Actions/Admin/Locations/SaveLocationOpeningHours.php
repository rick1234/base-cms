<?php

namespace App\Actions\Admin\Locations;

use App\Models\Cms\Location;
use App\Models\Cms\LocationSpecialOpeningHour;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class SaveLocationOpeningHours
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Location $location, array $data, ?Authenticatable $actor = null): Location
    {
        return DB::transaction(function () use ($location, $data, $actor): Location {
            foreach ((array) ($data['opening_hours'] ?? []) as $day => $row) {
                $location->openingHours()->updateOrCreate(
                    ['day' => (string) $day],
                    [
                        'opens_at' => $row['opens_at'] ?? null,
                        'closes_at' => $row['closes_at'] ?? null,
                        'is_closed' => (bool) ($row['is_closed'] ?? false),
                        'created_by' => $actor?->getAuthIdentifier(),
                        'updated_by' => $actor?->getAuthIdentifier(),
                    ],
                );
            }

            foreach ((array) ($data['special_opening_hours'] ?? []) as $row) {
                $special = ! empty($row['id'])
                    ? $location->specialOpeningHours()->whereKey((int) $row['id'])->first()
                    : null;

                if (! empty($row['delete'])) {
                    $special?->delete();

                    continue;
                }

                if (blank($row['title'] ?? null) && blank($row['date'] ?? null)) {
                    continue;
                }

                $special ??= new LocationSpecialOpeningHour([
                    'location_id' => $location->id,
                    'created_by' => $actor?->getAuthIdentifier(),
                ]);
                $special->fill([
                    'title' => $row['title'] ?? null,
                    'date' => $row['date'] ?? now()->toDateString(),
                    'opens_at' => $row['opens_at'] ?? null,
                    'closes_at' => $row['closes_at'] ?? null,
                    'is_closed' => (bool) ($row['is_closed'] ?? false),
                    'updated_by' => $actor?->getAuthIdentifier(),
                ])->save();
            }

            return $location->refresh();
        });
    }
}
