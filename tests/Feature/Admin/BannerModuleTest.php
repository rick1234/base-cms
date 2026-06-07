<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Cms\Banner;
use App\Models\Cms\BannerCategory;
use App\Models\Cms\BannerImage;
use App\Models\Cms\BannerTranslation;
use App\Models\Cms\WebsiteTemplate;
use App\Livewire\Admin\Banners\BannerTranslationEditor;
use Database\Seeders\BannerModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BannerModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_banner_module_seeder_creates_demo_banners_without_duplicates(): void
    {
        $this->seed(BannerModuleSeeder::class);
        $this->seed(BannerModuleSeeder::class);

        $this->assertSame(3, BannerCategory::query()->count());
        $this->assertSame(15, Banner::query()->count());
        $this->assertSame(15, BannerImage::query()->count());
        $this->assertSame(30, BannerTranslation::query()->count());

        $this->assertDatabaseHas('banners', [
            'title' => 'Seeded banner 01',
            'status' => 'published',
            'template_section' => 'homepage_hero',
        ]);
        $this->assertDatabaseHas('banners', [
            'title' => 'Seeded banner 07',
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('banner_translations', [
            'locale' => 'nl',
            'title' => 'Zomeractie',
        ]);
        $this->assertDatabaseHas('banner_translations', [
            'locale' => 'en',
            'title' => 'Summer campaign',
        ]);
        $this->assertDatabaseHas('banner_categories', [
            'slug' => 'homepage-hero',
            'name' => 'Homepage hero',
        ]);
        $this->assertDatabaseHas('banner_categories', [
            'slug' => 'slider',
            'name' => 'Slider',
        ]);
        $this->assertDatabaseHas('banner_images', [
            'caption' => 'Summer campaign',
            'original_filename' => 'seeded-banner-01.jpg',
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

    public function test_banner_edit_screen_uses_content_style_tabs_template_sections_and_preserves_tab_data(): void
    {
        $admin = User::factory()->admin()->create();
        WebsiteTemplate::query()->create([
            'handle' => 'base',
            'name' => 'Base Template',
            'is_active' => true,
            'defined_sections' => [
                ['handle' => 'homepage_right_block', 'label' => 'Homepage Right Block', 'type' => 'banner'],
            ],
        ]);
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
            'template_section' => 'homepage_right_block',
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
            ->assertSee('Afbeeldingen')
            ->assertSee('Template')
            ->assertSee('Vertalingen')
            ->assertSee('name="active_tab" value="general"', false)
            ->assertSee('Hero')
            ->assertDontSee('id="banner_image"', false)
            ->assertDontSee('banner-language-list', false);

        $this->actingAs($admin)
            ->get("/admin/banner/{$banner->id}/edit/image")
            ->assertNotFound();

        $this->actingAs($admin)
            ->get("/admin/banner/{$banner->id}/edit/images")
            ->assertOk()
            ->assertSee('content-album', false)
            ->assertSee('name="images[]"', false)
            ->assertDontSee('name="active_tab"', false)
            ->assertDontSee('categories-tree', false)
            ->assertDontSee('banner-language-list', false);

        $this->actingAs($admin)
            ->get("/admin/banner/{$banner->id}/edit/template")
            ->assertOk()
            ->assertSee('name="active_tab" value="template"', false)
            ->assertSee('template_section', false)
            ->assertSee('template-wireframe', false)
            ->assertSee('template-wireframe-section is-selected', false)
            ->assertSee(route('admin.templates.index'), false)
            ->assertSee('Template module openen')
            ->assertSee('Homepage Right Block');

        $this->actingAs($admin)
            ->get("/admin/banner/{$banner->id}/edit/translations")
            ->assertOk()
            ->assertSee('banner-translation-editor', false)
            ->assertSee('banner-translation-list', false)
            ->assertSee('Held banner')
            ->assertSee('Nederlands')
            ->assertDontSee('name="active_tab"', false)
            ->assertDontSee('banner-language-list', false)
            ->assertDontSee('translation_title_nl', false)
            ->assertDontSee('id="banner_image"', false);

        $this->actingAs($admin)
            ->post("/admin/banner/{$banner->id}", [
                'id' => $banner->id,
                'active_tab' => 'template',
                'template_section' => 'homepage_right_block',
            ])
            ->assertRedirect("/admin/banner/{$banner->id}/edit/template");

        $banner->refresh();

        $this->assertSame('published', $banner->status);
        $this->assertSame('_blank', $banner->metadata['target']);
        $this->assertSame('Original alt', $banner->metadata['alt_text']);
        $this->assertSame('homepage_right_block', $banner->template_section);
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

    public function test_banner_translation_editor_saves_language_content_and_metadata(): void
    {
        $admin = User::factory()->admin()->create();
        $banner = Banner::query()->create([
            'title' => 'Fallback banner',
            'status' => 'published',
        ]);

        Livewire::actingAs($admin)
            ->test(BannerTranslationEditor::class, [
                'banner' => $banner,
                'locales' => [
                    'nl' => 'Nederlands',
                    'en' => 'English',
                ],
            ])
            ->assertSet('selectedLocale', 'nl')
            ->call('selectLocale', 'en')
            ->assertSet('selectedLocale', 'en')
            ->set('translations.en.title', 'English banner')
            ->set('translations.en.subtitle', 'English subtitle')
            ->set('translations.en.link_url', '/en/campaign')
            ->set('translations.en.button_text', 'Read more')
            ->set('translations.en.content', 'English banner body')
            ->set('translations.en.alt_text', 'Campaign hero')
            ->set('translations.en.aria_label', 'Open the campaign')
            ->set('translations.en.link_target', '_blank')
            ->call('save')
            ->assertSet('message', 'Banner translations saved.');

        $translation = BannerTranslation::query()
            ->where('banner_id', $banner->id)
            ->where('locale', 'en')
            ->firstOrFail();

        $this->assertSame('English banner', $translation->title);
        $this->assertSame('English subtitle', $translation->subtitle);
        $this->assertSame('/en/campaign', $translation->link_url);
        $this->assertSame('Read more', $translation->button_text);
        $this->assertSame('English banner body', $translation->content);
        $this->assertSame('Campaign hero', $translation->metadata['alt_text']);
        $this->assertSame('Open the campaign', $translation->metadata['aria_label']);
        $this->assertSame('_blank', $translation->metadata['link_target']);
    }

    public function test_banner_image_album_uploads_multiple_images_for_frontend_slider(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $banner = Banner::query()->create([
            'title' => 'Slider banner',
            'status' => 'published',
            'template_section' => 'footer_banner',
        ]);

        $this->actingAs($admin)
            ->post("/admin/banner/{$banner->id}/images", [
                'images' => [
                    UploadedFile::fake()->image('slide-one.jpg', 1200, 500),
                    UploadedFile::fake()->image('slide-two.jpg', 1200, 500),
                ],
            ])
            ->assertRedirect();

        $this->assertSame(2, BannerImage::query()->where('banner_id', $banner->id)->count());
        $this->assertSame(
            ['Slide One', 'Slide Two'],
            $banner->images()->pluck('caption')->all(),
        );
        $this->assertSame(
            ['Slide One', 'Slide Two'],
            Banner::query()
                ->published()
                ->forTemplateSection('footer_banner')
                ->firstOrFail()
                ->images()
                ->pluck('caption')
                ->all(),
        );
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
