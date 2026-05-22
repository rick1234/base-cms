<?php

namespace Tests\Feature\Frontend;

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
}
