<?php

namespace Tests\Feature\Api\V1;

use App\Models\Cms\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_published_pages_as_json_api_resources(): void
    {
        Page::factory()->create([
            'slug' => 'published-page',
            'title' => 'Published page',
        ]);
        Page::factory()->draft()->create([
            'slug' => 'draft-page',
            'title' => 'Draft page',
        ]);

        $this->getJson('/api/v1/pages')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'published-page')
            ->assertJsonMissing(['slug' => 'draft-page']);
    }

    public function test_it_shows_a_published_page(): void
    {
        Page::factory()->create([
            'slug' => 'api-page',
            'title' => 'API page',
        ]);

        $this->getJson('/api/v1/pages/api-page')
            ->assertOk()
            ->assertJsonPath('data.slug', 'api-page')
            ->assertJsonPath('data.title', 'API page');
    }

    public function test_it_hides_draft_pages_from_public_api(): void
    {
        Page::factory()->draft()->create([
            'slug' => 'hidden-draft',
        ]);

        $this->getJson('/api/v1/pages/hidden-draft')->assertNotFound();
    }
}
