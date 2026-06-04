<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Cms\Banner;
use App\Models\Cms\BannerCategory;
use App\Models\Cms\BannerTranslation;
use Database\Seeders\BannerModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_banner_module_seeder_creates_demo_banner_without_duplicates(): void
    {
        $this->seed(BannerModuleSeeder::class);
        $this->seed(BannerModuleSeeder::class);

        $this->assertSame(2, BannerCategory::query()->count());
        $this->assertSame(1, Banner::query()->count());
        $this->assertSame(2, BannerTranslation::query()->count());

        $this->assertDatabaseHas('banners', [
            'title' => 'Seeded banner',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('banner_translations', [
            'locale' => 'nl',
            'title' => 'Voorbeeldbanner',
        ]);
        $this->assertDatabaseHas('banner_translations', [
            'locale' => 'en',
            'title' => 'Seeded banner',
        ]);
        $this->assertDatabaseHas('banner_categories', [
            'slug' => 'homepage-hero',
            'name' => 'Homepage hero',
        ]);
    }

    public function test_admin_can_create_banner_with_legacy_fields_categories_translations_and_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $category = BannerCategory::query()->create([
            'name' => 'Campaigns',
            'slug' => 'campaigns',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/banner/edit', [
                'status' => '1',
                'startdatum' => '01-05-2026',
                'einddatum' => '31-05-2026',
                'categorie' => [$category->id],
                'titelnl' => 'Legacy banner title',
                'subtitelnl' => 'Legacy subtitle',
                'linknl' => '/actie',
                'knoptekstnl' => 'Bekijk',
                'tekstnl' => 'Legacy banner body',
                'alt_text' => 'Campaign banner',
                'image' => UploadedFile::fake()->image('banner.jpg', 1200, 500),
            ])
            ->assertRedirect('/admin/banner/1/edit');

        $this->assertDatabaseHas('banners', [
            'title' => 'Legacy banner title',
            'link_url' => '/actie',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('banner_category_banner', [
            'banner_category_id' => $category->id,
            'banner_id' => 1,
        ]);
        $this->assertDatabaseHas('banner_translations', [
            'banner_id' => 1,
            'locale' => 'nl',
            'title' => 'Legacy banner title',
            'button_text' => 'Bekijk',
        ]);

        $banner = Banner::query()->firstOrFail();
        $this->assertStringStartsWith('storage/admin/uploads/banner/', $banner->image_path);

        $this->actingAs($admin)
            ->get('/admin/banner')
            ->assertOk()
            ->assertSee('Legacy banner title')
            ->assertSee('Campaigns')
            ->assertDontSee('ajax/duplicateItem', false)
            ->assertDontSee('Dupliceren');
    }

    public function test_bulk_upload_delete_image_and_duplicate_banner_endpoints(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $category = BannerCategory::query()->create([
            'name' => 'Bulk',
            'slug' => 'bulk',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/banner/bulkUploader', [
                'categories' => [$category->id],
                'banners' => [
                    UploadedFile::fake()->image('bulk-one.jpg', 900, 300),
                    UploadedFile::fake()->image('bulk-two.jpg', 900, 300),
                ],
            ])
            ->assertRedirect('/admin/banner');

        $this->assertSame(2, Banner::query()->count());
        $this->assertDatabaseHas('banner_category_banner', [
            'banner_category_id' => $category->id,
            'banner_id' => 1,
        ]);

        $banner = Banner::query()->firstOrFail();
        BannerTranslation::query()->create([
            'banner_id' => $banner->id,
            'locale' => 'nl',
            'title' => 'Duplicated translation',
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/banner/ajax/duplicateItem', [
                'itemId' => $banner->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $copy = Banner::query()->where('title', 'like', '%copy')->firstOrFail();

        $this->assertSame('draft', $copy->status);
        $this->assertDatabaseHas('banner_translations', [
            'banner_id' => $copy->id,
            'title' => 'Duplicated translation copy',
        ]);
        $this->assertDatabaseHas('banner_category_banner', [
            'banner_category_id' => $category->id,
            'banner_id' => $copy->id,
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/banner/ajax/deleteAfbeelding', [
                'bannerId' => $banner->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'image_path' => null,
        ]);
    }

    public function test_banner_edit_screen_uses_content_style_tabs_and_preserves_tab_data(): void
    {
        $admin = User::factory()->admin()->create();
        $category = BannerCategory::query()->create([
            'name' => 'Hero',
            'slug' => 'hero',
            'status' => 'active',
        ]);
        $banner = Banner::query()->create([
            'title' => 'Hero banner',
            'status' => 'published',
            'starts_at' => '2026-05-01',
            'ends_at' => '2026-05-31',
            'image_path' => 'storage/admin/uploads/banner/hero.jpg',
            'metadata' => [
                'alt_text' => 'Original alt',
                'target' => '_blank',
            ],
        ]);
        $banner->categories()->sync([$category->id => ['sort_order' => 1]]);
        BannerTranslation::query()->create([
            'banner_id' => $banner->id,
            'locale' => 'nl',
            'title' => 'Held banner',
            'link_url' => '/actie',
        ]);

        $this->actingAs($admin)
            ->get("/admin/banner/{$banner->id}/edit")
            ->assertOk()
            ->assertSee('tabmenu', false)
            ->assertSee('Algemeen')
            ->assertSee('Afbeelding')
            ->assertSee('Vertalingen')
            ->assertSee('name="active_tab" value="general"', false)
            ->assertSee('Hero')
            ->assertDontSee('id="banner_image"', false)
            ->assertDontSee('banner-language-list', false);

        $this->actingAs($admin)
            ->get("/admin/banner/{$banner->id}/edit/image")
            ->assertOk()
            ->assertSee('name="active_tab" value="image"', false)
            ->assertSee('id="banner_image"', false)
            ->assertSee('Original alt')
            ->assertDontSee('categories-tree', false)
            ->assertDontSee('banner-language-list', false);

        $this->actingAs($admin)
            ->get("/admin/banner/{$banner->id}/edit/translations")
            ->assertOk()
            ->assertSee('name="active_tab" value="translations"', false)
            ->assertSee('banner-language-list', false)
            ->assertSee('translation_title_nl', false)
            ->assertSee('Held banner')
            ->assertDontSee('id="banner_image"', false);

        $this->actingAs($admin)
            ->post("/admin/banner/{$banner->id}", [
                'id' => $banner->id,
                'active_tab' => 'image',
                'alt_text' => 'Updated alt',
            ])
            ->assertRedirect("/admin/banner/{$banner->id}/edit/image");

        $banner->refresh();

        $this->assertSame('published', $banner->status);
        $this->assertSame('_blank', $banner->metadata['target']);
        $this->assertSame('Updated alt', $banner->metadata['alt_text']);
        $this->assertDatabaseHas('banner_category_banner', [
            'banner_category_id' => $category->id,
            'banner_id' => $banner->id,
        ]);
        $this->assertDatabaseHas('banner_translations', [
            'banner_id' => $banner->id,
            'locale' => 'nl',
            'title' => 'Held banner',
        ]);
    }

    public function test_banner_categories_support_legacy_fields_and_delete_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/banner/categorieen/edit', [
                'naam' => 'Legacy banner category',
                'slug' => 'legacy-banner-category',
                'omschrijving' => 'Category body',
                'status' => 1,
            ])
            ->assertRedirect('/admin/banner/categorieen/1/edit');

        $this->assertDatabaseHas('banner_categories', [
            'name' => 'Legacy banner category',
            'description' => 'Category body',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->delete('/admin/banner/categorieen/1')
            ->assertRedirect('/admin/banner/categorieen');

        $this->assertSoftDeleted('banner_categories', [
            'id' => 1,
        ]);
    }
}
