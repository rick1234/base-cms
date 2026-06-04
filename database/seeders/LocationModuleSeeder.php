<?php

namespace Database\Seeders;

use App\Models\Cms\Location;
use App\Models\Cms\LocationCategory;
use App\Models\Cms\LocationImage;
use App\Models\Cms\LocationOpeningHour;
use App\Models\Cms\LocationSpecialOpeningHour;
use Database\Seeders\Support\SeederFiles;
use Illuminate\Database\Seeder;

class LocationModuleSeeder extends Seeder
{
    public function run(): void
    {
        $rootCategory = LocationCategory::query()->firstOrCreate(
            ['slug' => 'locations'],
            [
                'name' => 'Locations',
                'description' => 'Seeded location root category.',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );

        $showroomCategory = LocationCategory::query()->firstOrCreate(
            ['slug' => 'showrooms'],
            [
                'parent_id' => $rootCategory->id,
                'name' => 'Showrooms',
                'description' => 'Seeded showroom category.',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );

        $location = Location::query()->updateOrCreate(
            ['name' => 'Seeded Amsterdam location'],
            [
                'street_address' => 'Keizersgracht 1',
                'postal_code' => '1015 CC',
                'city' => 'Amsterdam',
                'country_code' => 'NL',
                'email' => 'amsterdam@example.com',
                'phone' => '+31 20 000 0000',
                'website_url' => 'https://example.com/amsterdam',
                'chamber_of_commerce_number' => '12345678',
                'description' => 'Seeded location used to verify the rebuilt locations module.',
                'latitude' => '52.3738',
                'longitude' => '4.8909',
                'map_info' => 'Seeded map information.',
                'status' => 'active',
                'active_from' => now()->toDateString(),
                'sort_order' => 1,
                'metadata' => null,
            ],
        );

        $location->categories()->sync([
            $rootCategory->id => ['sort_order' => 1],
            $showroomCategory->id => ['sort_order' => 2],
        ]);

        $locationImagePath = SeederFiles::publicImage('seed-image-07.jpg', 'admin/uploads/locations/images', 'seeded-location.jpg');

        LocationImage::query()->updateOrCreate(
            ['location_id' => $location->id, 'image_path' => $locationImagePath],
            [
                'folder' => 'storage/admin/uploads/locations/images',
                'caption' => 'Seeded location image',
                'sort_order' => 1,
            ],
        );

        foreach (array_keys(Location::dayNames()) as $day) {
            LocationOpeningHour::query()->updateOrCreate(
                ['location_id' => $location->id, 'day' => $day],
                [
                    'opens_at' => in_array($day, ['5', '6'], true) ? null : '09:00',
                    'closes_at' => in_array($day, ['5', '6'], true) ? null : '17:00',
                    'is_closed' => in_array($day, ['5', '6'], true),
                ],
            );
        }

        LocationSpecialOpeningHour::query()->updateOrCreate(
            ['location_id' => $location->id, 'title' => 'Seeded holiday opening'],
            [
                'date' => now()->addMonth()->toDateString(),
                'opens_at' => '10:00',
                'closes_at' => '14:00',
                'is_closed' => false,
            ],
        );
    }
}
