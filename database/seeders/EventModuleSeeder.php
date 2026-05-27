<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cms\Event;
use App\Models\Cms\EventAttachment;
use App\Models\Cms\EventCategory;
use App\Models\Cms\EventImage;
use App\Models\Cms\EventPart;
use App\Models\Cms\Form;
use Illuminate\Database\Seeder;

class EventModuleSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@example.com')->value('id');

        $registrationForm = Form::query()->firstOrCreate(
            ['slug' => 'event-registration'],
            [
                'name' => 'Event registration',
                'description' => 'Seeded form placeholder for event registration.',
                'submit_text' => 'Register',
                'success_message' => 'Thanks, your registration has been received.',
                'recipient_email' => 'admin@example.com',
                'status' => 'active',
                'sort_order' => 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

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

        $launch = $this->event([
            'title' => 'Seeded Laravel launch event',
            'subtitle' => 'A complete event module record',
            'slug' => 'seeded-laravel-launch-event',
            'intro' => 'This seeded event demonstrates event categories, dates, form assignment, parts, images, and attachments.',
            'body' => 'Use this record to inspect the recreated admin/evenementen/edit screen after a fresh install.',
            'meta_description' => 'Seeded event for the Laravel rebuilt events module.',
            'form_id' => $registrationForm->id,
            'starts_at' => now()->addMonth()->toDateString(),
            'ends_at' => now()->addMonth()->addDay()->toDateString(),
            'sort_order' => 1,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        $launch->categories()->sync([
            $events->id => ['sort_order' => 1],
            $workshops->id => ['sort_order' => 2],
        ]);

        $this->media($launch, $adminId);
        $this->parts($launch, $adminId);

        $workshop = $this->event([
            'title' => 'Seeded content workshop',
            'subtitle' => 'Overview and filter data',
            'slug' => 'seeded-content-workshop',
            'intro' => 'This event gives the overview screen a second row.',
            'body' => 'It is linked only to the Workshops category so child-category filters can be checked.',
            'meta_description' => 'Second seeded event for overview filtering.',
            'starts_at' => now()->addMonths(2)->toDateString(),
            'ends_at' => now()->addMonths(2)->toDateString(),
            'sort_order' => 2,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        $workshop->categories()->sync([
            $workshops->id => ['sort_order' => 1],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function event(array $attributes): Event
    {
        return Event::query()->updateOrCreate(
            ['slug' => $attributes['slug']],
            [
                'locale' => 'nl',
                'status' => 'published',
                'active_from' => now()->subDay()->toDateString(),
                'active_until' => now()->addYear()->toDateString(),
                ...$attributes,
            ],
        );
    }

    private function media(Event $event, ?int $adminId): void
    {
        EventImage::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'image_path' => 'admin/cms/img/icons/modules/evenementen.svg',
            ],
            [
                'folder' => 'admin/cms/img/icons/modules/',
                'caption' => 'Seeded event image',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );

        EventAttachment::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'url' => 'admin/cms/img/logo-cms-white.svg',
            ],
            [
                'name' => 'Seeded event attachment',
                'type' => 'image/svg+xml',
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );
    }

    private function parts(Event $event, ?int $adminId): void
    {
        EventPart::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'title' => 'Doors open',
            ],
            [
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
                'title' => 'Module walkthrough',
            ],
            [
                'starts_at' => now()->addMonth()->setTime(10, 0),
                'ends_at' => now()->addMonth()->setTime(11, 30),
                'sort_order' => 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        );
    }
}
