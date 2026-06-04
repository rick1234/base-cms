<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Content\ContentBlockEditor;
use App\Livewire\Admin\Events\EventImageAlbum;
use App\Livewire\Admin\Events\EventScheduleEditor;
use App\Models\Cms\Event;
use App\Models\Cms\EventAttachment;
use App\Models\Cms\EventCategory;
use App\Models\Cms\EventImage;
use App\Models\Cms\EventPart;
use App\Models\Cms\EventScheduleGroup;
use App\Models\Cms\Form;
use App\Models\User;
use Database\Seeders\EventModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EventModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_module_seeder_creates_demo_events_without_duplicates(): void
    {
        $this->seed(EventModuleSeeder::class);
        $this->seed(EventModuleSeeder::class);

        $this->assertSame(2, EventCategory::query()->count());
        $this->assertSame(4, Event::query()->count());
        $this->assertSame(2, EventScheduleGroup::query()->count());
        $this->assertSame(4, EventPart::query()->count());
        $this->assertSame(2, EventImage::query()->count());
        $this->assertSame(2, EventAttachment::query()->count());

        $this->assertDatabaseHas('event_categories', [
            'slug' => 'events',
            'name' => 'Events',
        ]);
        $this->assertDatabaseHas('events', [
            'slug' => 'seeded-laravel-launch-event',
            'locale' => 'nl',
            'title' => 'Laravel lanceringsevenement',
        ]);
        $this->assertDatabaseHas('events', [
            'slug' => 'seeded-laravel-launch-event-en',
            'locale' => 'en',
            'title' => 'Seeded Laravel launch event',
        ]);
        $this->assertDatabaseHas('event_schedule_groups', [
            'name' => 'Dag 1',
            'sort_order' => 1,
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

    public function test_event_overview_uses_shared_category_picker_and_no_duplicate_action(): void
    {
        $admin = User::factory()->admin()->create();
        $category = EventCategory::query()->create([
            'name' => 'Agenda',
            'slug' => 'agenda',
            'status' => 'active',
        ]);
        $event = Event::query()->create([
            'title' => 'Listed event',
            'slug' => 'listed-event',
            'status' => 'published',
            'starts_at' => '2026-06-10',
        ]);
        $event->categories()->sync([$category->id => ['sort_order' => 1]]);

        $response = $this->actingAs($admin)
            ->get('/admin/evenementen')
            ->assertOk()
            ->assertSee('listing-category-picker', false)
            ->assertSee('listing-category-native', false)
            ->assertSee('name="categoryId"', false)
            ->assertDontSee('<select name="categoryId"', false)
            ->assertDontSee('/admin/evenementen/ajax/duplicateItem', false)
            ->assertDontSee('attach_file', false)
            ->assertSee('Listed event')
            ->assertSee('Agenda');

        $this->actingAs($admin)
            ->get("/admin/evenementen/{$event->id}/edit")
            ->assertOk()
            ->assertDontSee('btn-duplicate', false)
            ->assertDontSee('Dupliceren');
    }

    public function test_event_edit_screen_uses_tabs_and_content_style_page_builder(): void
    {
        $admin = User::factory()->admin()->create();
        $category = EventCategory::query()->create([
            'name' => 'Congresses',
            'slug' => 'congresses',
            'status' => 'active',
        ]);
        $form = Form::query()->create([
            'name' => 'Event registration',
            'slug' => 'event-registration',
            'status' => 'active',
        ]);
        $event = Event::query()->create([
            'title' => 'Tabbed event',
            'subtitle' => 'A focused event',
            'slug' => 'tabbed-event',
            'locale' => 'nl',
            'intro' => 'Old intro should stay stored only.',
            'body' => 'Old body should stay stored only.',
            'meta_description' => 'Original SEO description',
            'status' => 'published',
            'active_from' => '2026-05-01',
            'active_until' => '2026-12-31',
            'starts_at' => '2026-06-10',
            'ends_at' => '2026-06-11',
            'form_id' => $form->id,
        ]);
        $event->categories()->sync([$category->id => ['sort_order' => 1]]);

        $generalResponse = $this->actingAs($admin)
            ->get("/admin/evenementen/{$event->id}/edit")
            ->assertOk()
            ->assertSee('item-tabs-container', false)
            ->assertSee('Algemeen')
            ->assertSee('Tijdschema')
            ->assertSee('Formulier')
            ->assertSee('Bijlagen')
            ->assertSee('Fotoalbum')
            ->assertSee('SEO')
            ->assertSee('name="active_tab" value="general"', false)
            ->assertSee('name="title"', false)
            ->assertSee('categories-tree', false)
            ->assertSee('Evenement periode')
            ->assertSee('Publicatie periode')
            ->assertSee('Blokken toevoegen')
            ->assertSee('content-block-editor', false)
            ->assertDontSee('name="intro"', false)
            ->assertDontSee('name="body"', false)
            ->assertDontSee('Evenement Onderdelen');

        $generalContent = $generalResponse->getContent();

        $this->assertGreaterThanOrEqual(3, substr_count($generalContent, 'class="col-4"'));
        $this->assertStringNotContainsString('class="content-section"', $generalContent);

        $this->actingAs($admin)
            ->get("/admin/evenementen/{$event->id}/edit?tab=seo")
            ->assertRedirect("/admin/evenementen/{$event->id}/edit/seo");

        $this->actingAs($admin)
            ->get("/admin/evenementen/{$event->id}/edit/seo")
            ->assertOk()
            ->assertSee('name="active_tab" value="seo"', false)
            ->assertSee('name="meta_description"', false)
            ->assertSee('Original SEO description')
            ->assertDontSee('name="title"', false)
            ->assertDontSee('name="intro"', false)
            ->assertDontSee('name="body"', false)
            ->assertDontSee('content-block-editor', false);

        $this->actingAs($admin)
            ->get("/admin/evenementen/{$event->id}/edit/schedule")
            ->assertOk()
            ->assertSee('event-schedule-editor-form', false)
            ->assertSee('event-schedule-editor', false)
            ->assertSee('Tijdschema sets')
            ->assertSee('Geen tijdschema sets gevonden.')
            ->assertSee('Set toevoegen')
            ->assertDontSee('name="new_parts"', false)
            ->assertDontSee('Evenement Onderdelen')
            ->assertDontSee('content-block-editor', false);

        $this->actingAs($admin)
            ->get("/admin/evenementen/{$event->id}/edit/images")
            ->assertOk()
            ->assertSee('event-image-album', false)
            ->assertSee('data-content-image-editor', false)
            ->assertSee("/admin/evenementen/ajax/uploadAfbeelding?id={$event->id}", false)
            ->assertSee('Upload selectie')
            ->assertDontSee('btn-remove', false)
            ->assertDontSee('plupload-container', false)
            ->assertDontSee('Reeds gekoppelde fotos')
            ->assertDontSee('name="active_tab" value="images"', false)
            ->assertDontSee('content-block-editor', false);

        $this->actingAs($admin)
            ->post("/admin/evenementen/{$event->id}", [
                'id' => $event->id,
                'active_tab' => 'seo',
                'meta_description' => 'Updated SEO description',
            ])
            ->assertRedirect("/admin/evenementen/{$event->id}/edit/seo");

        $event->refresh();

        $this->assertSame('Tabbed event', $event->title);
        $this->assertSame('tabbed-event', $event->slug);
        $this->assertSame('Updated SEO description', $event->meta_description);
        $this->assertSame($form->id, $event->form_id);
        $this->assertSame([$category->id], $event->categories()->pluck('event_categories.id')->all());
    }

    public function test_livewire_content_block_editor_saves_event_structured_blocks(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::query()->create([
            'title' => 'Block editor event',
            'slug' => 'block-editor-event',
            'locale' => 'nl',
            'status' => 'draft',
        ]);

        Livewire::actingAs($admin)
            ->test(ContentBlockEditor::class, [
                'ownerType' => 'event',
                'eventId' => $event->id,
            ])
            ->set('data.blocks', [
                [
                    'type' => 'title',
                    'data' => [
                        'uuid' => '2b353495-f7fc-44ab-8baa-16437a232fb6',
                        'layout' => '100',
                        'data' => [
                            'title' => 'A structured event title',
                            'level' => 'h2',
                        ],
                        'settings' => [
                            'alignment' => 'center',
                        ],
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'uuid' => 'f11acb28-1ec0-47f7-8f65-a9d3df35d57b',
                        'layout' => '50',
                        'data' => [
                            'content' => '<p>Structured event body</p>',
                        ],
                        'settings' => [
                            'alignment' => 'left',
                            'background_style' => 'none',
                            'intro_style' => false,
                        ],
                    ],
                ],
            ])
            ->call('save')
            ->assertSet('message', 'Contentblokken opgeslagen.');

        $event->refresh();

        $this->assertSame('title', $event->structured_blocks[0]['type']);
        $this->assertSame('A structured event title', $event->structured_blocks[0]['data']['title']);
        $this->assertSame('text', $event->structured_blocks[1]['type']);
        $this->assertSame('<p>Structured event body</p>', $event->structured_blocks[1]['data']['content']);
        $this->assertSame('50', $event->structured_blocks[1]['layout']);
    }

    public function test_livewire_event_schedule_editor_saves_grouped_sets_and_items(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::query()->create([
            'title' => 'Schedule event',
            'slug' => 'schedule-event',
            'locale' => 'nl',
            'status' => 'draft',
            'starts_at' => '2026-06-10',
        ]);

        Livewire::actingAs($admin)
            ->test(EventScheduleEditor::class, ['eventId' => $event->id])
            ->call('addGroup')
            ->set('groups.0.name', 'Dag 1')
            ->call('addItem', 0)
            ->set('groups.0.items.0.title', 'Opening')
            ->set('groups.0.items.0.date', '2026-06-10')
            ->set('groups.0.items.0.starts_at', '09:00')
            ->set('groups.0.items.0.ends_at', '09:30')
            ->set('groups.0.items.0.content', 'Ontvangst en registratie.')
            ->call('addGroup')
            ->set('groups.1.name', 'Locatie Theater')
            ->call('addItem', 1)
            ->set('groups.1.items.0.title', 'Panelgesprek')
            ->set('groups.1.items.0.date', '2026-06-10')
            ->set('groups.1.items.0.starts_at', '11:00')
            ->set('groups.1.items.0.ends_at', '12:00')
            ->call('toggleGroup', 1)
            ->call('save')
            ->assertSet('message', 'Tijdschema opgeslagen.');

        $groups = EventScheduleGroup::query()
            ->where('event_id', $event->id)
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(2, $groups);
        $this->assertSame('Dag 1', $groups[0]->name);
        $this->assertSame('Locatie Theater', $groups[1]->name);
        $this->assertTrue($groups[1]->is_collapsed);

        $opening = EventPart::query()->where('title', 'Opening')->firstOrFail();
        $panel = EventPart::query()->where('title', 'Panelgesprek')->firstOrFail();

        $this->assertSame($groups[0]->id, $opening->event_schedule_group_id);
        $this->assertSame('2026-06-10 09:00:00', $opening->starts_at?->format('Y-m-d H:i:s'));
        $this->assertSame('Ontvangst en registratie.', $opening->content);
        $this->assertSame($groups[1]->id, $panel->event_schedule_group_id);
    }

    public function test_livewire_event_schedule_editor_sorts_groups_and_items(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::query()->create([
            'title' => 'Sortable schedule event',
            'slug' => 'sortable-schedule-event',
            'status' => 'draft',
        ]);
        $firstGroup = EventScheduleGroup::query()->create([
            'event_id' => $event->id,
            'name' => 'Dag 1',
            'sort_order' => 1,
        ]);
        $secondGroup = EventScheduleGroup::query()->create([
            'event_id' => $event->id,
            'name' => 'Dag 2',
            'sort_order' => 2,
        ]);
        $firstPart = EventPart::query()->create([
            'event_id' => $event->id,
            'event_schedule_group_id' => $firstGroup->id,
            'title' => 'Eerste item',
            'sort_order' => 1,
        ]);
        $secondPart = EventPart::query()->create([
            'event_id' => $event->id,
            'event_schedule_group_id' => $firstGroup->id,
            'title' => 'Tweede item',
            'sort_order' => 2,
        ]);

        Livewire::actingAs($admin)
            ->test(EventScheduleEditor::class, ['eventId' => $event->id])
            ->call('sortGroup', $firstGroup->id, $secondGroup->id, 'before')
            ->call('sortItem', $firstPart->id, $secondPart->id, 'before')
            ->call('save')
            ->assertSet('message', 'Tijdschema opgeslagen.');

        $this->assertSame(1, $secondGroup->refresh()->sort_order);
        $this->assertSame(2, $firstGroup->refresh()->sort_order);
        $this->assertSame(1, $secondPart->refresh()->sort_order);
        $this->assertSame(2, $firstPart->refresh()->sort_order);
    }

    public function test_livewire_event_schedule_editor_adopts_legacy_flat_parts(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::query()->create([
            'title' => 'Legacy schedule event',
            'slug' => 'legacy-schedule-event',
            'locale' => 'nl',
            'status' => 'draft',
            'starts_at' => '2026-06-10',
        ]);
        $part = EventPart::query()->create([
            'event_id' => $event->id,
            'title' => 'Los onderdeel',
            'starts_at' => '2026-06-10 10:00:00',
            'sort_order' => 1,
        ]);

        Livewire::actingAs($admin)
            ->test(EventScheduleEditor::class, ['eventId' => $event->id])
            ->assertSet('groups.0.name', 'Programma')
            ->assertSet('groups.0.items.0.title', 'Los onderdeel')
            ->call('save')
            ->assertSet('message', 'Tijdschema opgeslagen.');

        $group = EventScheduleGroup::query()->where('event_id', $event->id)->firstOrFail();

        $this->assertSame('Programma', $group->name);
        $this->assertSame($group->id, $part->refresh()->event_schedule_group_id);
    }

    public function test_livewire_event_image_album_saves_seo_fields_and_sorts_images(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::query()->create([
            'title' => 'Album event',
            'slug' => 'album-event',
            'status' => 'published',
        ]);
        $firstImage = EventImage::query()->create([
            'event_id' => $event->id,
            'image_path' => 'storage/events/images/first.jpg',
            'caption' => 'First',
            'sort_order' => 1,
            'is_decorative' => false,
        ]);
        $secondImage = EventImage::query()->create([
            'event_id' => $event->id,
            'image_path' => 'storage/events/images/second.jpg',
            'caption' => 'Second',
            'sort_order' => 2,
            'is_decorative' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(EventImageAlbum::class, ['event' => $event])
            ->call('editImage', $firstImage->id)
            ->set("imageForms.{$firstImage->id}.caption", 'Updated caption')
            ->set("imageForms.{$firstImage->id}.alt_text", 'Useful alt text')
            ->set("imageForms.{$firstImage->id}.title_text", 'Image title')
            ->set("imageForms.{$firstImage->id}.description", 'Image description for editors.')
            ->set("imageForms.{$firstImage->id}.credit", 'Photographer')
            ->set("imageForms.{$firstImage->id}.is_decorative", false)
            ->call('saveImage', $firstImage->id)
            ->assertSet('message', 'Image SEO options saved.')
            ->call('moveImage', $secondImage->id, $firstImage->id, 'after')
            ->assertSet('message', 'Image order saved.');

        $this->assertDatabaseHas('event_images', [
            'id' => $firstImage->id,
            'caption' => 'Updated caption',
            'alt_text' => 'Useful alt text',
            'title_text' => 'Image title',
            'description' => 'Image description for editors.',
            'credit' => 'Photographer',
            'is_decorative' => false,
            'sort_order' => 2,
        ]);
        $this->assertSame(1, $secondImage->refresh()->sort_order);
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

    public function test_event_duplicate_routes_are_removed(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/evenementen/ajax/duplicateItem', ['itemId' => 1])
            ->assertNotFound();

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/cms/evenementen/ajax/duplicateItem.php', ['itemId' => 1])
            ->assertNotFound();
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
