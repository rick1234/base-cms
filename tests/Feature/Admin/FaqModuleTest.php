<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Cms\FaqAttachment;
use App\Models\Cms\FaqCategory;
use App\Models\Cms\FaqImage;
use App\Models\Cms\FaqItem;
use App\Models\Cms\FaqVideo;
use Database\Seeders\FaqModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaqModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_module_seeder_creates_demo_faq_without_duplicates(): void
    {
        $this->seed(FaqModuleSeeder::class);
        $this->seed(FaqModuleSeeder::class);

        $this->assertSame(2, FaqCategory::query()->count());
        $this->assertSame(1, FaqItem::query()->count());
        $this->assertSame(1, FaqImage::query()->count());
        $this->assertSame(1, FaqAttachment::query()->count());
        $this->assertSame(1, FaqVideo::query()->count());

        $this->assertDatabaseHas('faq_items', [
            'slug' => 'seeded-faq-question',
            'question' => 'Seeded FAQ question',
        ]);
        $this->assertDatabaseHas('faq_categories', [
            'slug' => 'support',
            'name' => 'Support',
        ]);
    }

    public function test_admin_can_create_faq_item_with_legacy_fields_categories_and_attachments(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $category = FaqCategory::query()->create([
            'name' => 'General',
            'slug' => 'general',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/faq/edit', [
                'question' => 'How does the rebuilt FAQ work?',
                'answer' => 'It uses Laravel controllers, requests, Eloquent models, and Blade views.',
                'slug' => 'rebuilt-faq-question',
                'locale' => 'nl',
                'status' => '1',
                'active_from' => '2026-05-01',
                'categorie' => [$category->id],
                'attachment_names' => ['FAQ sheet'],
                'attachment_files' => [
                    UploadedFile::fake()->create('faq-sheet.pdf', 12, 'application/pdf'),
                ],
            ])
            ->assertRedirect('/admin/faq/1/edit');

        $this->assertDatabaseHas('faq_items', [
            'question' => 'How does the rebuilt FAQ work?',
            'body' => 'It uses Laravel controllers, requests, Eloquent models, and Blade views.',
            'slug' => 'rebuilt-faq-question',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('faq_category_faq_item', [
            'faq_category_id' => $category->id,
            'faq_item_id' => 1,
        ]);
        $this->assertDatabaseHas('faq_attachments', [
            'faq_item_id' => 1,
            'name' => 'FAQ sheet',
        ]);

        $this->actingAs($admin)
            ->get('/admin/faq')
            ->assertOk()
            ->assertSee('How does the rebuilt FAQ work?')
            ->assertSee('General');
    }

    public function test_faq_media_and_video_endpoints_upload_rename_sort_save_and_delete(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $faqItem = FaqItem::query()->create([
            'question' => 'Media FAQ',
            'slug' => 'media-faq',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post("/admin/faq/ajax/uploadFotoalbumAfbeelding?id={$faqItem->id}", [
                'image' => UploadedFile::fake()->image('faq.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $firstImage = FaqImage::query()->firstOrFail();
        $secondImage = FaqImage::query()->create([
            'faq_item_id' => $faqItem->id,
            'image_path' => 'storage/faq/images/second.jpg',
            'caption' => 'Second',
            'sort_order' => 2,
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/faq/ajax/updateAfbeeldingnaam', [
                'uploadId' => $firstImage->id,
                'uploadName' => 'Renamed FAQ image',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/faq/ajax/updateSortIndex', [
                'sort_index' => "{$secondImage->id},{$firstImage->id}",
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->actingAs($admin)
            ->post('/admin/faq/editVideo', [
                'id' => $faqItem->id,
                'videos' => [
                    ['title' => 'FAQ video', 'url' => 'https://example.com/faq-video', 'provider' => 'external'],
                ],
            ])
            ->assertRedirect("/admin/faq/{$faqItem->id}/videos");

        $video = FaqVideo::query()->firstOrFail();

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/faq/ajax/deleteVideo', [
                'videoid' => $video->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/faq/ajax/deleteAfbeelding', [
                'id' => $firstImage->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('faq_images', [
            'id' => $firstImage->id,
            'caption' => 'Renamed FAQ image',
        ]);
        $this->assertDatabaseHas('faq_images', [
            'id' => $secondImage->id,
            'sort_order' => 1,
        ]);
        $this->assertDatabaseMissing('faq_videos', [
            'id' => $video->id,
        ]);
        $this->assertSoftDeleted('faq_images', [
            'id' => $firstImage->id,
        ]);
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

    public function test_faq_items_can_be_duplicated_with_media_attachments_categories_and_videos(): void
    {
        $admin = User::factory()->admin()->create();
        $category = FaqCategory::query()->create([
            'name' => 'Duplication',
            'slug' => 'duplication',
            'status' => 'active',
        ]);
        $faqItem = FaqItem::query()->create([
            'question' => 'Original FAQ',
            'slug' => 'original-faq',
            'body' => 'Original answer.',
            'status' => 'published',
        ]);
        $faqItem->categories()->sync([$category->id => ['sort_order' => 1]]);

        FaqAttachment::query()->create([
            'faq_item_id' => $faqItem->id,
            'name' => 'Original attachment',
            'url' => 'storage/faq/attachments/original.pdf',
            'sort_order' => 1,
        ]);
        FaqImage::query()->create([
            'faq_item_id' => $faqItem->id,
            'image_path' => 'storage/faq/images/original.jpg',
            'caption' => 'Original image',
            'sort_order' => 1,
        ]);
        FaqVideo::query()->create([
            'faq_item_id' => $faqItem->id,
            'title' => 'Original video',
            'url' => 'https://example.com/original-video',
            'provider' => 'external',
            'sort_order' => 1,
        ]);

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
        $this->assertDatabaseHas('faq_category_faq_item', [
            'faq_category_id' => $category->id,
            'faq_item_id' => $copy->id,
        ]);
        $this->assertDatabaseHas('faq_attachments', [
            'faq_item_id' => $copy->id,
            'name' => 'Original attachment',
        ]);
        $this->assertDatabaseHas('faq_images', [
            'faq_item_id' => $copy->id,
            'caption' => 'Original image',
        ]);
        $this->assertDatabaseHas('faq_videos', [
            'faq_item_id' => $copy->id,
            'title' => 'Original video',
        ]);
    }
}
