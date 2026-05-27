<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Cms\Event;
use App\Models\Cms\EventAttachment;
use App\Models\Cms\EventCategory;
use App\Models\Cms\EventImage;
use App\Models\Cms\EventPart;
use App\Models\Cms\Form;
use Database\Seeders\EventModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_module_seeder_creates_demo_events_without_duplicates(): void
    {
        $this->seed(EventModuleSeeder::class);
        $this->seed(EventModuleSeeder::class);

        $this->assertSame(2, EventCategory::query()->count());
        $this->assertSame(2, Event::query()->count());
        $this->assertSame(2, EventPart::query()->count());
        $this->assertSame(1, EventImage::query()->count());
        $this->assertSame(1, EventAttachment::query()->count());

        $this->assertDatabaseHas('event_categories', [
            'slug' => 'events',
            'name' => 'Events',
        ]);
        $this->assertDatabaseHas('events', [
            'slug' => 'seeded-laravel-launch-event',
            'title' => 'Seeded Laravel launch event',
        ]);
        $this->assertDatabaseHas('event_parts', [
            'title' => 'Module walkthrough',
        ]);
    }

    public function test_admin_can_create_an_event_with_categories_attachments_parts_and_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $category = EventCategory::query()->create([
            'name' => 'Agenda',
            'slug' => 'agenda',
            'status' => 'active',
        ]);
        $form = Form::query()->create([
            'name' => 'Registration',
            'slug' => 'registration',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/evenementen/edit', [
                'title' => 'Legacy rebuilt event',
                'subtitle' => 'A proper event subtitle',
                'slug' => 'legacy-rebuilt-event',
                'locale' => 'nl',
                'status' => 'published',
                'active_from' => '2026-05-01',
                'active_until' => '2026-12-31',
                'starts_at' => '2026-06-10',
                'ends_at' => '2026-06-11',
                'meta_description' => 'SEO event description',
                'form_id' => $form->id,
                'categories' => [$category->id],
                'attachment_names' => ['Program'],
                'attachment_files' => [
                    UploadedFile::fake()->create('program.pdf', 12, 'application/pdf'),
                ],
                'new_parts' => [
                    [
                        'title' => 'Opening',
                        'date' => '2026-06-10',
                        'starts_at' => '09:00',
                        'ends_at' => '09:30',
                    ],
                ],
                'image_caption' => 'Event hero',
                'image' => UploadedFile::fake()->image('event-hero.jpg'),
            ])
            ->assertRedirect('/admin/evenementen/1/edit');

        $this->assertDatabaseHas('events', [
            'title' => 'Legacy rebuilt event',
            'subtitle' => 'A proper event subtitle',
            'slug' => 'legacy-rebuilt-event',
            'form_id' => $form->id,
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('event_category_event', [
            'event_category_id' => $category->id,
            'event_id' => 1,
        ]);

        $this->assertDatabaseHas('event_attachments', [
            'event_id' => 1,
            'name' => 'Program',
        ]);

        $this->assertDatabaseHas('event_parts', [
            'event_id' => 1,
            'title' => 'Opening',
        ]);

        $this->assertDatabaseHas('event_images', [
            'event_id' => 1,
            'caption' => 'Event hero',
        ]);

        $this->actingAs($admin)
            ->get('/admin/evenementen')
            ->assertOk()
            ->assertSee('Legacy rebuilt event')
            ->assertSee('Agenda');
    }

    public function test_event_photo_album_ajax_endpoints_upload_rename_sort_and_delete_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $event = Event::query()->create([
            'title' => 'Photo event',
            'slug' => 'photo-event',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post("/admin/evenementen/ajax/uploadAfbeelding?id={$event->id}", [
                'image' => UploadedFile::fake()->image('hero.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $firstImage = EventImage::query()->firstOrFail();
        $secondImage = EventImage::query()->create([
            'event_id' => $event->id,
            'image_path' => 'storage/events/images/second.jpg',
            'caption' => 'Second',
            'sort_order' => 2,
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/evenementen/ajax/updateAfbeeldingnaam', [
                'uploadId' => $firstImage->id,
                'uploadName' => 'Renamed event hero',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('event_images', [
            'id' => $firstImage->id,
            'caption' => 'Renamed event hero',
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/evenementen/ajax/updateSortIndex', [
                'sort_index' => "{$secondImage->id},{$firstImage->id}",
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('event_images', [
            'id' => $secondImage->id,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/evenementen/ajax/deleteAfbeelding', [
                'id' => $firstImage->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSoftDeleted('event_images', [
            'id' => $firstImage->id,
        ]);
    }

    public function test_events_can_be_duplicated_with_media_attachments_categories_and_parts(): void
    {
        $admin = User::factory()->admin()->create();
        $category = EventCategory::query()->create([
            'name' => 'Updates',
            'slug' => 'updates',
            'status' => 'active',
        ]);
        $event = Event::query()->create([
            'title' => 'Original event',
            'slug' => 'original-event',
            'status' => 'published',
        ]);
        $event->categories()->sync([$category->id => ['sort_order' => 1]]);

        EventAttachment::query()->create([
            'event_id' => $event->id,
            'name' => 'Original attachment',
            'url' => 'storage/events/attachments/original.pdf',
            'sort_order' => 1,
        ]);
        EventImage::query()->create([
            'event_id' => $event->id,
            'image_path' => 'storage/events/images/original.jpg',
            'caption' => 'Original image',
            'sort_order' => 1,
        ]);
        EventPart::query()->create([
            'event_id' => $event->id,
            'title' => 'Original part',
            'starts_at' => '2026-06-10 09:00:00',
            'ends_at' => '2026-06-10 10:00:00',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/evenementen/ajax/duplicateItem', [
                'itemId' => $event->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $copy = Event::query()->whereKeyNot($event->id)->firstOrFail();

        $this->assertSame('draft', $copy->status);
        $this->assertDatabaseHas('event_category_event', [
            'event_category_id' => $category->id,
            'event_id' => $copy->id,
        ]);
        $this->assertDatabaseHas('event_attachments', [
            'event_id' => $copy->id,
            'name' => 'Original attachment',
        ]);
        $this->assertDatabaseHas('event_images', [
            'event_id' => $copy->id,
            'caption' => 'Original image',
        ]);
        $this->assertDatabaseHas('event_parts', [
            'event_id' => $copy->id,
            'title' => 'Original part',
        ]);
    }

    public function test_event_categories_support_legacy_fields_and_delete_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/evenementen/categorieen/edit', [
                'naam' => 'Legacy event category',
                'slug' => 'legacy-event-category',
                'content' => 'Category body',
                'actief' => 1,
            ])
            ->assertRedirect('/admin/evenementen/categorieen/1/edit');

        $this->assertDatabaseHas('event_categories', [
            'name' => 'Legacy event category',
            'description' => 'Category body',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->delete('/admin/evenementen/categorieen/1')
            ->assertRedirect('/admin/evenementen/categorieen');

        $this->assertSoftDeleted('event_categories', [
            'id' => 1,
        ]);
    }

    public function test_event_parts_can_be_deleted_through_the_legacy_ajax_route(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::query()->create([
            'title' => 'Part delete event',
            'slug' => 'part-delete-event',
            'status' => 'published',
        ]);
        $part = EventPart::query()->create([
            'event_id' => $event->id,
            'title' => 'Delete me',
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/evenementen/ajax/deleteOnderdeel', [
                'part_id' => $part->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('event_parts', [
            'id' => $part->id,
        ]);
    }
}
