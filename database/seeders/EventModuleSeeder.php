<?php

namespace Database\Seeders;

use App\Models\Cms\Event;
use App\Models\Cms\EventAttachment;
use App\Models\Cms\EventCategory;
use App\Models\Cms\EventImage;
use App\Models\Cms\EventPart;
use App\Models\Cms\EventScheduleGroup;
use App\Models\Cms\Form;
use App\Models\User;
use Database\Seeders\Support\SeederFiles;
use Illuminate\Database\Seeder;

class EventModuleSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@example.com')->value('id');

        $registrationForms = [];

        foreach ($this->registrationForms() as $formData) {
            $registrationForms[$formData['locale']] = Form::query()->updateOrCreate(
                ['slug' => $formData['slug']],
                [
                    ...$formData,
                    'recipient_email' => 'admin@example.com',
                    'status' => 'active',
                    'sort_order' => $formData['locale'] === 'nl' ? 2 : 3,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ],
            );
        }

        $events = EventCategory::query()->firstOrCreate(
            ['slug' => 'events'],
            [
                'name' => 'Events',
                'description' => 'Seeded parent category for the rebuilt events module.',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        $workshops = EventCategory::query()->firstOrCreate(
            ['slug' => 'workshops'],
            [
                'parent_id' => $events->id,
                'name' => 'Workshops',
                'description' => 'Seeded child category for event filtering.',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        foreach ($this->events() as $eventData) {
            $event = $this->event([
                ...$eventData,
                'form_id' => ($eventData['uses_form'] ?? false) ? $registrationForms[$eventData['locale']]->id : null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);

            if ($eventData['kind'] === 'launch') {
                $event->categories()->sync([
                    $events->id => ['sort_order' => 1],
                    $workshops->id => ['sort_order' => 2],
                ]);

                $this->media($event, $adminId, $eventData['locale']);
                $this->parts($event, $adminId, $eventData['locale']);

                continue;
            }

            $event->categories()->sync([
                $workshops->id => ['sort_order' => 1],
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function registrationForms(): array
    {
        return [
            [
                'name' => 'Evenement aanmelden',
                'slug' => 'event-registration',
                'locale' => 'nl',
                'description' => 'Voorbeeldformulier voor evenementaanmeldingen.',
                'submit_text' => 'Aanmelden',
                'success_message' => 'Bedankt, je aanmelding is ontvangen.',
            ],
            [
                'name' => 'Event registration',
                'slug' => 'event-registration-en',
                'locale' => 'en',
                'description' => 'Seeded form placeholder for event registration.',
                'submit_text' => 'Register',
                'success_message' => 'Thanks, your registration has been received.',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function events(): array
    {
        return [
            [
                'kind' => 'launch',
                'locale' => 'nl',
                'title' => 'Laravel lanceringsevenement',
                'subtitle' => 'Een compleet evenementrecord',
                'slug' => 'seeded-laravel-launch-event',
                'intro' => 'Dit voorbeeldevenement toont categorieen, datums, formulieren, onderdelen, afbeeldingen en bijlagen.',
                'body' => 'Gebruik dit record om het vernieuwde admin/evenementen/edit scherm na een nieuwe installatie te controleren.',
                'meta_description' => 'Voorbeeldevenement voor de vernieuwde Laravel evenementenmodule.',
                'uses_form' => true,
                'starts_at' => now()->addMonth()->toDateString(),
                'ends_at' => now()->addMonth()->addDay()->toDateString(),
                'sort_order' => 1,
            ],
            [
                'kind' => 'launch',
                'locale' => 'en',
                'title' => 'Seeded Laravel launch event',
                'subtitle' => 'A complete event module record',
                'slug' => 'seeded-laravel-launch-event-en',
                'intro' => 'This seeded event demonstrates event categories, dates, form assignment, parts, images, and attachments.',
                'body' => 'Use this record to inspect the recreated admin/evenementen/edit screen after a fresh install.',
                'meta_description' => 'Seeded event for the Laravel rebuilt events module.',
                'uses_form' => true,
                'starts_at' => now()->addMonth()->toDateString(),
                'ends_at' => now()->addMonth()->addDay()->toDateString(),
                'sort_order' => 2,
            ],
            [
                'kind' => 'workshop',
                'locale' => 'nl',
                'title' => 'Voorbeeldworkshop pagina-editor',
                'subtitle' => 'Overzicht en filterdata',
                'slug' => 'seeded-content-workshop',
                'intro' => 'Dit evenement geeft het overzichtsscherm een tweede rij.',
                'body' => 'Het is alleen gekoppeld aan de workshopcategorie zodat subcategoriefilters gecontroleerd kunnen worden.',
                'meta_description' => 'Tweede voorbeeldevenement voor overzichtsfilters.',
                'starts_at' => now()->addMonths(2)->toDateString(),
                'ends_at' => now()->addMonths(2)->toDateString(),
                'sort_order' => 3,
            ],
            [
                'kind' => 'workshop',
                'locale' => 'en',
                'title' => 'Seeded page editor workshop',
                'subtitle' => 'Overview and filter data',
                'slug' => 'seeded-content-workshop-en',
                'intro' => 'This event gives the overview screen a second row.',
                'body' => 'It is linked only to the Workshops category so child-category filters can be checked.',
                'meta_description' => 'Second seeded event for overview filtering.',
                'starts_at' => now()->addMonths(2)->toDateString(),
                'ends_at' => now()->addMonths(2)->toDateString(),
                'sort_order' => 4,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function event(array $attributes): Event
    {
        return Event::query()->updateOrCreate(
            ['slug' => $attributes['slug']],
            [
                'status' => 'published',
                'active_from' => now()->subDay()->toDateString(),
                'active_until' => now()->addYear()->toDateString(),
                ...collect($attributes)->except(['kind', 'uses_form'])->all(),
            ],
        );
    }

    private function media(Event $event, ?int $adminId, string $locale): void
    {
        $imagePath = SeederFiles::publicImage('seed-image-06.png', 'events/images', 'seeded-event-'.$locale.'.png');
        $attachmentPath = SeederFiles::publicDocument('event-attachment.txt', 'events/attachments', 'seeded-event-'.$locale.'.txt');

        EventImage::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'image_path' => $imagePath,
            ],
            [
                'folder' => 'storage/events/images/',
                'caption' => $locale === 'nl' ? 'Voorbeeldafbeelding evenement' : 'Seeded event image',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        EventAttachment::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'url' => $attachmentPath,
            ],
            [
                'name' => $locale === 'nl' ? 'Voorbeeldbijlage evenement' : 'Seeded event attachment',
                'type' => 'text/plain',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );
    }

    private function parts(Event $event, ?int $adminId, string $locale): void
    {
        $parts = $locale === 'nl'
            ? ['Deuren open', 'Module rondleiding']
            : ['Doors open', 'Module walkthrough'];
        $group = EventScheduleGroup::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'name' => $locale === 'nl' ? 'Dag 1' : 'Day 1',
            ],
            [
                'sort_order' => 1,
                'is_collapsed' => false,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        EventPart::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'title' => $parts[0],
            ],
            [
                'event_schedule_group_id' => $group->id,
                'starts_at' => now()->addMonth()->setTime(9, 0),
                'ends_at' => now()->addMonth()->setTime(9, 30),
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        EventPart::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'title' => $parts[1],
            ],
            [
                'event_schedule_group_id' => $group->id,
                'starts_at' => now()->addMonth()->setTime(10, 0),
                'ends_at' => now()->addMonth()->setTime(11, 30),
                'sort_order' => 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );
    }
}
