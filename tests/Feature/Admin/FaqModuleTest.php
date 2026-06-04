<?php

namespace Tests\Feature\Admin;

use App\Models\Cms\FaqCategory;
use App\Models\Cms\FaqItem;
use App\Models\Cms\NavigationMenu;
use App\Models\Cms\NavigationMenuItem;
use App\Models\User;
use Database\Seeders\FaqModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FaqModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_module_seeder_creates_demo_faq_without_duplicates(): void
    {
        $this->seed(FaqModuleSeeder::class);
        $this->seed(FaqModuleSeeder::class);

        $this->assertSame(2, FaqCategory::query()->count());
        $this->assertSame(2, FaqItem::query()->count());
        $this->assertDatabaseCount('faq_attachments', 0);
        $this->assertFalse(Schema::hasTable('faq_images'));
        $this->assertFalse(Schema::hasTable('faq_videos'));

        $this->assertDatabaseHas('faq_items', [
            'slug' => 'seeded-faq-question',
            'locale' => 'nl',
            'question' => 'Hoe werkt de vernieuwde FAQ module?',
            'intro' => null,
            'meta_description' => null,
        ]);
        $this->assertDatabaseHas('faq_items', [
            'slug' => 'seeded-faq-question-en',
            'locale' => 'en',
            'question' => 'How does the rebuilt FAQ module work?',
        ]);
        $this->assertDatabaseHas('faq_categories', [
            'slug' => 'support',
            'name' => 'Support',
        ]);
    }

    public function test_admin_can_create_simple_faq_item_with_categories_and_more_info_navigation_link(): void
    {
        $admin = User::factory()->admin()->create();
        $category = FaqCategory::query()->create([
            'name' => 'General',
            'slug' => 'general',
            'status' => 'active',
        ]);
        $navigationItem = $this->navigationItem();
        $secondNavigationItem = NavigationMenuItem::query()->create([
            'navigation_menu_id' => $navigationItem->navigation_menu_id,
            'title' => 'Planning page',
            'link_type' => 'custom',
            'custom_url' => '/planning',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post('/admin/faq/edit', [
                'question' => 'How does the rebuilt FAQ work?',
                'answer' => '<p>It uses a focused question and answer editor.</p>',
                'status' => '1',
                'categorie' => [$category->id],
                'more_info_links' => [
                    [
                        'navigation_item_id' => $navigationItem->id,
                        'label' => 'Read more',
                    ],
                    [
                        'navigation_item_id' => $secondNavigationItem->id,
                        'label' => 'Planning',
                    ],
                    [
                        'navigation_item_id' => null,
                        'label' => 'Ignored because no target is selected',
                    ],
                ],
            ])
            ->assertRedirect('/admin/faq/1/edit');

        $faqItem = FaqItem::query()->firstOrFail();

        $this->assertSame('How does the rebuilt FAQ work?', $faqItem->question);
        $this->assertSame('<p>It uses a focused question and answer editor.</p>', $faqItem->body);
        $this->assertSame('how-does-the-rebuilt-faq-work', $faqItem->slug);
        $this->assertSame('published', $faqItem->status);
        $this->assertSame([
            'navigation_item_id' => $navigationItem->id,
            'label' => 'Read more',
        ], $faqItem->metadata['more_info']);
        $this->assertSame([
            [
                'navigation_item_id' => $navigationItem->id,
                'label' => 'Read more',
            ],
            [
                'navigation_item_id' => $secondNavigationItem->id,
                'label' => 'Planning',
            ],
        ], $faqItem->metadata['more_info_links']);
        $this->assertDatabaseHas('faq_category_faq_item', [
            'faq_category_id' => $category->id,
            'faq_item_id' => $faqItem->id,
        ]);
        $this->assertDatabaseCount('faq_attachments', 0);

        $this->actingAs($admin)
            ->get('/admin/faq')
            ->assertOk()
            ->assertSee('How does the rebuilt FAQ work?')
            ->assertSee('General')
            ->assertDontSee('ajax/duplicateItem', false)
            ->assertDontSee('Dupliceren');
    }

    public function test_faq_edit_screen_is_simplified(): void
    {
        $admin = User::factory()->admin()->create();
        $faqItem = FaqItem::query()->create([
            'question' => 'Simple FAQ',
            'slug' => 'simple-faq',
            'body' => '<p>Simple answer.</p>',
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/faq/{$faqItem->id}/edit")
            ->assertOk()
            ->assertSee('Vraag')
            ->assertSee('Antwoord')
            ->assertSee('Meer informatie')
            ->assertSee('data-wysiwyg-editor', false)
            ->assertSee('class="wysiwyg-hidden-input"', false)
            ->assertSee('data-form-managed-list="faq-more-info-links"', false)
            ->assertSee('data-form-managed-list-add="faq-more-info-links"', false)
            ->assertSee('more_info_links[0][navigation_item_id]', false)
            ->assertSee('listing-category-picker', false)
            ->assertDontSee('name="slug"', false)
            ->assertDontSee('name="locale"', false)
            ->assertDontSee('name="more_info_navigation_item_id"', false)
            ->assertDontSee('name="more_info_label"', false)
            ->assertDontSee('active_from', false)
            ->assertDontSee('meta_description', false)
            ->assertDontSee('Fotoalbum')
            ->assertDontSee("Video's");

        $html = $response->getContent();
        $questionPosition = strpos($html, 'id="question"');
        $statusPosition = strpos($html, 'id="status"');
        $answerPosition = strpos($html, 'id="faq-answer-label"');

        $this->assertSame(1, substr_count($html, '<textarea'));
        $this->assertIsInt($questionPosition);
        $this->assertIsInt($statusPosition);
        $this->assertIsInt($answerPosition);
        $this->assertGreaterThan($questionPosition, $statusPosition);
        $this->assertLessThan($answerPosition, $statusPosition);
    }

    public function test_faq_media_and_video_routes_are_removed(): void
    {
        $admin = User::factory()->admin()->create();
        $faqItem = FaqItem::query()->create([
            'question' => 'Media-free FAQ',
            'slug' => 'media-free-faq',
            'status' => 'published',
        ]);

        $this->assertFalse(Route::has('admin.faq.images'));
        $this->assertFalse(Route::has('admin.faq.videos'));
        $this->assertFalse(Route::has('admin.faq.image.upload'));
        $this->assertFalse(Route::has('admin.faq.video.delete'));

        $this->actingAs($admin)
            ->get("/admin/faq/{$faqItem->id}/images")
            ->assertNotFound();

        $this->actingAs($admin)
            ->post("/admin/faq/ajax/uploadFotoalbumAfbeelding?id={$faqItem->id}")
            ->assertNotFound();

        $this->actingAs($admin)
            ->post('/admin/faq/editVideo', [
                'id' => $faqItem->id,
            ])
            ->assertNotFound();
    }

    public function test_faq_categories_support_legacy_fields_and_delete_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/faq/categorieen/edit', [
                'naam' => 'Legacy FAQ category',
                'slug' => 'legacy-faq-category',
                'omschrijving' => 'Category body',
                'status' => 1,
            ])
            ->assertRedirect('/admin/faq/categorieen/1/edit');

        $this->assertDatabaseHas('faq_categories', [
            'name' => 'Legacy FAQ category',
            'description' => 'Category body',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->delete('/admin/faq/categorieen/1')
            ->assertRedirect('/admin/faq/categorieen');

        $this->assertSoftDeleted('faq_categories', [
            'id' => 1,
        ]);
    }

    public function test_faq_items_can_be_duplicated_with_categories_and_more_info_metadata(): void
    {
        $admin = User::factory()->admin()->create();
        $category = FaqCategory::query()->create([
            'name' => 'Duplication',
            'slug' => 'duplication',
            'status' => 'active',
        ]);
        $navigationItem = $this->navigationItem();
        $faqItem = FaqItem::query()->create([
            'question' => 'Original FAQ',
            'slug' => 'original-faq',
            'body' => 'Original answer.',
            'status' => 'published',
            'metadata' => [
                'more_info' => [
                    'navigation_item_id' => $navigationItem->id,
                    'label' => 'Read more',
                ],
            ],
        ]);
        $faqItem->categories()->sync([$category->id => ['sort_order' => 1]]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/faq/ajax/duplicateItem', [
                'itemId' => $faqItem->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $copy = FaqItem::query()
            ->where('slug', 'like', 'original-faq-copy-%')
            ->firstOrFail();

        $this->assertSame('draft', $copy->status);
        $this->assertSame($faqItem->metadata, $copy->metadata);
        $this->assertDatabaseHas('faq_category_faq_item', [
            'faq_category_id' => $category->id,
            'faq_item_id' => $copy->id,
        ]);
        $this->assertDatabaseCount('faq_attachments', 0);
    }

    private function navigationItem(): NavigationMenuItem
    {
        $menu = NavigationMenu::query()->create([
            'handle' => 'primary',
            'name' => 'Primary navigation',
            'is_active' => true,
        ]);

        return NavigationMenuItem::query()->create([
            'navigation_menu_id' => $menu->id,
            'title' => 'More info page',
            'link_type' => 'custom',
            'custom_url' => '/more-info',
            'is_active' => true,
        ]);
    }
}
