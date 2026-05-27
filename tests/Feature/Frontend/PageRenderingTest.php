<?php

namespace Tests\Feature\Frontend;

use App\Models\Cms\ContentItem;
use App\Models\Cms\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_without_seeded_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Base CMS');
    }

    public function test_frontend_feedback_messages_render_through_closable_flash_component(): void
    {
        $this->withSession([
            'flash_notification' => [
                [
                    'message' => 'Frontend saved.',
                    'level' => 'success',
                    'title' => null,
                    'overlay' => false,
                    'important' => false,
                ],
            ],
        ])
            ->get('/')
            ->assertOk()
            ->assertSee('Frontend saved.')
            ->assertSee('data-flash-message', false)
            ->assertSee('data-flash-close', false);
    }

    public function test_common_laravel_session_status_messages_render_as_flash_messages(): void
    {
        $this->withSession(['status' => 'Profile updated.'])
            ->get('/')
            ->assertOk()
            ->assertSee('Profile updated.')
            ->assertSee('flash-message-success', false)
            ->assertSee('data-flash-close', false);
    }

    public function test_published_page_renders_with_seo_metadata(): void
    {
        Page::factory()->create([
            'slug' => 'about',
            'title' => 'About the base',
            'body' => 'Reusable content lives here.',
            'meta_title' => 'About meta title',
            'meta_description' => 'About meta description for search results.',
        ]);

        $this->get('/about')
            ->assertOk()
            ->assertSee('About meta title')
            ->assertSee('About meta description for search results.')
            ->assertSee('Reusable content lives here.');
    }

    public function test_draft_page_is_not_publicly_available(): void
    {
        Page::factory()->draft()->create([
            'slug' => 'draft-page',
        ]);

        $this->get('/draft-page')->assertNotFound();
    }

    public function test_published_content_item_renders_from_public_slug(): void
    {
        ContentItem::query()->create([
            'slug' => 'public-content-item',
            'title' => 'Public content item',
            'subtitle' => 'Useful subtitle',
            'intro' => 'Reusable intro content.',
            'body' => 'Reusable body content.',
            'locale' => 'nl',
            'status' => 'published',
        ]);

        $this->get('/public-content-item')
            ->assertOk()
            ->assertSee('Public content item')
            ->assertSee('Useful subtitle')
            ->assertSee('Reusable body content.');
    }
}
