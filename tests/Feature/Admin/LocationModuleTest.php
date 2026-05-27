<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Cms\Location;
use App\Models\Cms\LocationCategory;
use App\Models\Cms\LocationImage;
use App\Models\Cms\LocationOpeningHour;
use App\Models\Cms\LocationSpecialOpeningHour;
use Database\Seeders\LocationModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocationModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_module_seeder_creates_demo_location_without_duplicates(): void
    {
        $this->seed(LocationModuleSeeder::class);
        $this->seed(LocationModuleSeeder::class);

        $this->assertSame(2, LocationCategory::query()->count());
        $this->assertSame(1, Location::query()->count());
        $this->assertSame(1, LocationImage::query()->count());
        $this->assertSame(7, LocationOpeningHour::query()->count());
        $this->assertSame(1, LocationSpecialOpeningHour::query()->count());

        $this->assertDatabaseHas('locations', [
            'name' => 'Seeded Amsterdam location',
            'city' => 'Amsterdam',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('location_categories', [
            'slug' => 'showrooms',
            'name' => 'Showrooms',
        ]);
    }

    public function test_admin_can_create_location_with_legacy_fields_and_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $category = LocationCategory::query()->create([
            'name' => 'Shops',
            'slug' => 'shops',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/vestigingen/edit', [
                'naam' => 'Legacy Amsterdam shop',
                'adres' => 'Dam 1',
                'postcode' => '1012 JS',
                'plaats' => 'Amsterdam',
                'landcode' => 'NL',
                'email' => 'shop@example.com',
                'telefoon' => '+31 20 000 0000',
                'url' => 'https://example.com/shop',
                'kvknummer' => '87654321',
                'omschrijving' => 'Legacy location description.',
                'google_maps_coordinaat_x' => '52.3731',
                'google_maps_coordinaat_y' => '4.8922',
                'google_maps_info' => 'Legacy map info.',
                'status' => '1',
                'startdatum' => '01-05-2026',
                'einddatum' => '31-05-2026',
                'slug' => 'legacy-amsterdam-shop',
                'seo_title' => 'Legacy Amsterdam shop',
                'categorie' => [$category->id],
            ])
            ->assertRedirect('/admin/vestigingen/1/edit');

        $this->assertDatabaseHas('locations', [
            'name' => 'Legacy Amsterdam shop',
            'street_address' => 'Dam 1',
            'postal_code' => '1012 JS',
            'city' => 'Amsterdam',
            'status' => 'active',
            'active_from' => '2026-05-01 00:00:00',
            'active_until' => '2026-05-31 00:00:00',
        ]);
        $this->assertDatabaseHas('location_category_location', [
            'location_category_id' => $category->id,
            'location_id' => 1,
        ]);

        $location = Location::query()->firstOrFail();
        $this->assertSame('legacy-amsterdam-shop', $location->metadata['slug']);

        $this->actingAs($admin)
            ->get('/admin/vestigingen')
            ->assertOk()
            ->assertSee('Location overview')
            ->assertSee('Legacy Amsterdam shop')
            ->assertSee('Shops');
    }

    public function test_location_media_endpoints_upload_rename_sort_and_delete_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $location = Location::query()->create([
            'name' => 'Media location',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post("/admin/vestigingen/ajax/uploadAfbeelding?id={$location->id}", [
                'image' => UploadedFile::fake()->image('location.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $firstImage = LocationImage::query()->firstOrFail();
        $secondImage = LocationImage::query()->create([
            'location_id' => $location->id,
            'image_path' => 'storage/locations/second.jpg',
            'caption' => 'Second',
            'sort_order' => 2,
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/vestigingen/ajax/updateAfbeeldingnaam', [
                'uploadId' => $firstImage->id,
                'uploadName' => 'Renamed location image',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/vestigingen/ajax/updateSortIndex', [
                'sort_index' => "{$secondImage->id},{$firstImage->id}",
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/vestigingen/ajax/deleteAfbeelding', [
                'id' => $firstImage->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('location_images', [
            'id' => $firstImage->id,
            'caption' => 'Renamed location image',
        ]);
        $this->assertDatabaseHas('location_images', [
            'id' => $secondImage->id,
            'sort_order' => 1,
        ]);
        $this->assertSoftDeleted('location_images', [
            'id' => $firstImage->id,
        ]);
    }

    public function test_location_opening_hours_can_be_saved_and_special_days_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $location = Location::query()->create([
            'name' => 'Hours location',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/vestigingen/editOpeningstijden', [
                'id' => $location->id,
                'opening_hours' => [
                    '0' => [
                        'opens_at' => '08:30',
                        'closes_at' => '17:30',
                        'is_closed' => '0',
                    ],
                    '6' => [
                        'is_closed' => '1',
                    ],
                ],
                'special_opening_hours' => [
                    [
                        'title' => 'Late night',
                        'date' => '2026-06-01',
                        'opens_at' => '10:00',
                        'closes_at' => '22:00',
                        'is_closed' => '0',
                    ],
                ],
            ])
            ->assertRedirect("/admin/vestigingen/{$location->id}/opening-hours");

        $this->assertDatabaseHas('location_opening_hours', [
            'location_id' => $location->id,
            'day' => '0',
            'is_closed' => false,
        ]);
        $this->assertDatabaseHas('location_opening_hours', [
            'location_id' => $location->id,
            'day' => '6',
            'is_closed' => true,
        ]);
        $this->assertDatabaseHas('location_special_opening_hours', [
            'location_id' => $location->id,
            'title' => 'Late night',
            'date' => '2026-06-01 00:00:00',
        ]);

        $mondayOpeningHour = LocationOpeningHour::query()
            ->where('location_id', $location->id)
            ->where('day', '0')
            ->firstOrFail();

        $this->assertSame('08:30', substr((string) $mondayOpeningHour->opens_at, 0, 5));
        $this->assertSame('17:30', substr((string) $mondayOpeningHour->closes_at, 0, 5));

        $specialOpeningHour = LocationSpecialOpeningHour::query()->firstOrFail();

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/vestigingen/ajax/deleteOpeningstijd', [
                'id' => $specialOpeningHour->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('location_special_opening_hours', [
            'id' => $specialOpeningHour->id,
        ]);
    }

    public function test_location_categories_support_legacy_fields_and_delete_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/vestigingen/categorieen/edit', [
                'naam' => 'Legacy location category',
                'slug' => 'legacy-location-category',
                'omschrijving' => 'Category body',
                'status' => 1,
                'navigatieHidden' => 1,
            ])
            ->assertRedirect('/admin/vestigingen/categorieen/1/edit');

        $this->assertDatabaseHas('location_categories', [
            'name' => 'Legacy location category',
            'description' => 'Category body',
            'status' => 'active',
            'is_hidden_from_navigation' => true,
        ]);

        $this->actingAs($admin)
            ->delete('/admin/vestigingen/categorieen/1')
            ->assertRedirect('/admin/vestigingen/categorieen');

        $this->assertSoftDeleted('location_categories', [
            'id' => 1,
        ]);
    }

    public function test_locations_can_be_duplicated_with_categories_images_and_opening_hours(): void
    {
        $admin = User::factory()->admin()->create();
        $category = LocationCategory::query()->create([
            'name' => 'Duplication',
            'slug' => 'duplication',
            'status' => 'active',
        ]);
        $location = Location::query()->create([
            'name' => 'Original location',
            'city' => 'Rotterdam',
            'status' => 'active',
        ]);
        $location->categories()->sync([$category->id => ['sort_order' => 1]]);

        LocationImage::query()->create([
            'location_id' => $location->id,
            'image_path' => 'storage/locations/original.jpg',
            'caption' => 'Original image',
            'sort_order' => 1,
        ]);
        LocationOpeningHour::query()->create([
            'location_id' => $location->id,
            'day' => '0',
            'opens_at' => '09:00',
            'closes_at' => '17:00',
            'is_closed' => false,
        ]);
        LocationSpecialOpeningHour::query()->create([
            'location_id' => $location->id,
            'title' => 'Original special day',
            'date' => '2026-06-01',
            'is_closed' => true,
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/vestigingen/ajax/duplicateItem', [
                'itemId' => $location->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $copy = Location::query()
            ->where('name', 'like', 'Original location - copy - %')
            ->firstOrFail();

        $this->assertDatabaseHas('location_category_location', [
            'location_category_id' => $category->id,
            'location_id' => $copy->id,
        ]);
        $this->assertDatabaseHas('location_images', [
            'location_id' => $copy->id,
            'caption' => 'Original image',
        ]);
        $this->assertDatabaseHas('location_opening_hours', [
            'location_id' => $copy->id,
            'day' => '0',
        ]);
        $this->assertDatabaseHas('location_special_opening_hours', [
            'location_id' => $copy->id,
            'title' => 'Original special day',
        ]);
    }
}
