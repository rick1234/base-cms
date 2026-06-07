<?php

namespace Tests\Feature\Frontend;

use App\Models\Cms\Event;
use App\Models\Cms\EventAttachment;
use App\Models\Cms\EventCategory;
use App\Models\Cms\EventPart;
use App\Models\Cms\EventScheduleGroup;
use App\Models\Cms\CmsLanguage;
use Database\Seeders\CountryLanguageSeeder;
use Database\Seeders\TranslationModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_overview_renders_published_events_and_filters_by_category(): void
    {
        $workshops = EventCategory::query()->create([
            'name' => 'Workshops',
            'slug' => 'workshops',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $meetups = EventCategory::query()->create([
            'name' => 'Meetups',
            'slug' => 'meetups',
            'status' => 'active',
            'sort_order' => 2,
        ]);

        $workshop = Event::query()->create([
            'title' => 'Public workshop',
            'slug' => 'public-workshop',
            'subtitle' => 'Learn the module',
            'locale' => 'nl',
            'status' => 'published',
            'active_from' => now()->subDay(),
            'starts_at' => now()->addWeek(),
            'sort_order' => 1,
        ]);
        $workshop->categories()->attach($workshops->id, ['sort_order' => 1]);

        $meetup = Event::query()->create([
            'title' => 'Public meetup',
            'slug' => 'public-meetup',
            'locale' => 'nl',
            'status' => 'published',
            'active_from' => now()->subDay(),
            'starts_at' => now()->addWeeks(2),
            'sort_order' => 2,
        ]);
        $meetup->categories()->attach($meetups->id, ['sort_order' => 1]);

        Event::query()->create([
            'title' => 'Draft event',
            'slug' => 'draft-event',
            'locale' => 'nl',
            'status' => 'draft',
            'starts_at' => now()->addWeek(),
        ]);

        $this->get('/events')
            ->assertOk()
            ->assertSee('Public workshop')
            ->assertSee('Learn the module')
            ->assertSee('Public meetup')
            ->assertDontSee('Draft event')
            ->assertSee('href="'.route('frontend.events.show', ['event' => 'public-workshop']).'"', false)
            ->assertSee('category=workshops', false);

        $this->get('/events?category=workshops')
            ->assertOk()
            ->assertSee('Public workshop')
            ->assertDontSee('Public meetup');
    }

    public function test_event_detail_renders_content_schedule_attachments_and_seo_metadata(): void
    {
        $category = EventCategory::query()->create([
            'name' => 'Launches',
            'slug' => 'launches',
            'status' => 'active',
        ]);

        $event = Event::query()->create([
            'title' => 'Laravel launch',
            'slug' => 'laravel-launch',
            'subtitle' => 'A frontend event detail',
            'intro' => 'Intro copy for the public event.',
            'body' => 'Detailed event body.',
            'meta_description' => 'Event detail meta description.',
            'locale' => 'nl',
            'status' => 'published',
            'active_from' => now()->subDay(),
            'starts_at' => now()->addMonth()->toDateString(),
            'ends_at' => now()->addMonth()->addDay()->toDateString(),
            'structured_blocks' => [
                [
                    'type' => 'text',
                    'uuid' => 'event-rendering-text-block',
                    'layout' => '100',
                    'data' => [
                        'content' => 'Structured event block.',
                    ],
                    'settings' => [
                        'alignment' => 'left',
                        'background_style' => 'none',
                        'intro_style' => false,
                    ],
                ],
            ],
        ]);
        $event->categories()->attach($category->id, ['sort_order' => 1]);

        $group = EventScheduleGroup::query()->create([
            'event_id' => $event->id,
            'name' => 'Day 1',
            'sort_order' => 1,
        ]);

        EventPart::query()->create([
            'event_id' => $event->id,
            'event_schedule_group_id' => $group->id,
            'title' => 'Doors open',
            'content' => 'Welcome coffee.',
            'starts_at' => now()->addMonth()->setTime(9, 0),
            'sort_order' => 1,
        ]);

        EventAttachment::query()->create([
            'event_id' => $event->id,
            'name' => 'Program PDF',
            'type' => 'application/pdf',
            'url' => 'storage/events/attachments/program.pdf',
            'sort_order' => 1,
        ]);

        $this->get('/events/laravel-launch')
            ->assertOk()
            ->assertSee('Laravel launch')
            ->assertSee('A frontend event detail')
            ->assertSee('Event detail meta description.')
            ->assertSee('Intro copy for the public event.')
            ->assertSee('Detailed event body.')
            ->assertSee('Structured event block.')
            ->assertSee('Day 1')
            ->assertSee('Doors open')
            ->assertSee('Welcome coffee.')
            ->assertSee('Program PDF')
            ->assertSee('application/ld+json', false);
    }

    public function test_localized_event_routes_use_the_active_locale(): void
    {
        $this->seed(CountryLanguageSeeder::class);
        $this->seed(TranslationModuleSeeder::class);

        CmsLanguage::query()->updateOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'slug' => 'english',
                'native_name' => 'English',
                'direction' => 'ltr',
                'status' => 'active',
                'is_enabled' => true,
                'is_default' => false,
            ],
        );

        Event::query()->create([
            'title' => 'Nederlands evenement',
            'slug' => 'nederlands-evenement',
            'locale' => 'nl',
            'status' => 'published',
            'active_from' => now()->subDay(),
            'starts_at' => now()->addWeek(),
        ]);

        Event::query()->create([
            'title' => 'English event',
            'slug' => 'english-event',
            'locale' => 'en',
            'status' => 'published',
            'active_from' => now()->subDay(),
            'starts_at' => now()->addWeek(),
        ]);

        $this->get('/en/events')
            ->assertOk()
            ->assertSee('English event')
            ->assertDontSee('Nederlands evenement');

        $this->get('/en/events/english-event')
            ->assertOk()
            ->assertSee('English event');

        $this->get('/en/events/nederlands-evenement')->assertNotFound();
    }

    public function test_inactive_events_are_not_publicly_available(): void
    {
        Event::query()->create([
            'title' => 'Expired event',
            'slug' => 'expired-event',
            'locale' => 'nl',
            'status' => 'published',
            'active_from' => now()->subMonth(),
            'active_until' => now()->subDay(),
            'starts_at' => now()->addWeek(),
        ]);

        $this->get('/events')
            ->assertOk()
            ->assertDontSee('Expired event');

        $this->get('/events/expired-event')->assertNotFound();
    }
}
