<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Content\ContentImageAlbum;
use App\Livewire\Admin\Content\ContentBlockEditor;
use App\Livewire\Admin\AttachmentManager;
use App\Livewire\Admin\CategoryTreeManager;
use App\Livewire\Admin\ListingOverview;
use App\Models\User;
use App\Models\Cms\ContentAttachment;
use App\Models\Cms\ContentBlock;
use App\Models\Cms\ContentBlockLayout;
use App\Models\Cms\ContentBlockPart;
use App\Models\Cms\ContentBlockPartContainer;
use App\Models\Cms\ContentCategory;
use App\Models\Cms\ContentCategoryImage;
use App\Models\Cms\ContentImage;
use App\Models\Cms\ContentItem;
use App\Models\Cms\ContentPreviewToken;
use App\Models\Cms\Form;
use Database\Seeders\ContentModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;

class ContentModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_module_seeder_creates_demo_content_without_duplicates(): void
    {
        $this->seed(ContentModuleSeeder::class);
        $this->seed(ContentModuleSeeder::class);

        $this->assertSame(2, ContentCategory::query()->count());
        $this->assertSame(2, ContentItem::query()->count());
        $this->assertSame(1, ContentImage::query()->count());
        $this->assertSame(1, ContentAttachment::query()->count());

        $this->assertDatabaseHas('content_categories', [
            'slug' => 'news',
            'name' => 'News',
        ]);
        $this->assertDatabaseHas('content_items', [
            'slug' => 'welcome-to-the-rebuilt-content-module',
            'title' => 'Welcome to the rebuilt content module',
        ]);
        $this->assertSame(
            ['text', 'image', 'video'],
            collect(ContentItem::query()
                ->where('slug', 'welcome-to-the-rebuilt-content-module')
                ->firstOrFail()
                ->structured_blocks)
                ->pluck('type')
                ->all(),
        );
        $this->assertDatabaseMissing('cms_modules', [
            'handle' => 'radio',
        ]);
    }

    public function test_admin_can_create_a_content_item_with_categories_and_attachments(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $category = ContentCategory::query()->create([
            'name' => 'Nieuws',
            'slug' => 'nieuws',
            'status' => 'active',
        ]);
        $form = Form::query()->create([
            'name' => 'Contact',
            'slug' => 'contact',
            'status' => 'active',
        ]);
        $legacyToggleField = 'twe'.'etit';
        $legacyMessageField = 'twit'.'terbericht';
        $legacyMetadataKey = 'last_'.'twe'.'et_request';

        $this->actingAs($admin)
            ->post('/admin/content/edit', [
                'title' => 'Legacy rebuilt item',
                'subtitle' => 'A proper subtitle',
                'slug' => 'legacy-rebuilt-item',
                'locale' => 'nl',
                'status' => 'published',
                'active_from' => '2026-05-01',
                'active_until' => '2026-12-31',
                'intro' => 'Legacy intro should be ignored',
                'body' => 'Legacy body should be ignored',
                'meta_description' => 'SEO description',
                'form_id' => $form->id,
                'categories' => [$category->id],
                $legacyToggleField => '1',
                $legacyMessageField => 'This should be ignored',
                'attachment_names' => ['Specification'],
                'attachment_files' => [
                    UploadedFile::fake()->create('specification.pdf', 12, 'application/pdf'),
                ],
            ])
            ->assertRedirect('/admin/content/1/edit');

        $this->assertDatabaseHas('content_items', [
            'title' => 'Legacy rebuilt item',
            'subtitle' => 'A proper subtitle',
            'slug' => 'legacy-rebuilt-item',
            'form_id' => $form->id,
            'status' => 'published',
        ]);

        $createdContentItem = ContentItem::query()->where('slug', 'legacy-rebuilt-item')->firstOrFail();

        $this->assertNull($createdContentItem->metadata[$legacyMetadataKey] ?? null);
        $this->assertNull($createdContentItem->intro);
        $this->assertNull($createdContentItem->body);

        $this->assertDatabaseHas('content_category_content_item', [
            'content_category_id' => $category->id,
            'content_item_id' => 1,
        ]);

        $this->assertDatabaseHas('content_attachments', [
            'content_item_id' => 1,
            'name' => 'Specification',
        ]);

        $this->actingAs($admin)
            ->get('/admin/content')
            ->assertOk()
            ->assertSee('Legacy rebuilt item')
            ->assertSee('Nieuws');
    }

    public function test_livewire_content_block_editor_saves_structured_blocks(): void
    {
        $admin = User::factory()->admin()->create();
        $contentItem = ContentItem::query()->create([
            'title' => 'Block editor item',
            'slug' => 'block-editor-item',
            'locale' => 'nl',
            'status' => 'draft',
        ]);

        Livewire::actingAs($admin)
            ->test(ContentBlockEditor::class, [
                'contentItemId' => $contentItem->id,
            ])
            ->set('data.blocks', [
                [
                    'type' => 'title',
                    'data' => [
                        'uuid' => '2b353495-f7fc-44ab-8baa-16437a232fb6',
                        'layout' => '100',
                        'data' => [
                            'title' => 'A structured title',
                            'level' => 'h2',
                        ],
                        'settings' => [
                            'alignment' => 'center',
                        ],
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'uuid' => 'f11acb28-1ec0-47f7-8f65-a9d3df35d57b',
                        'layout' => '50',
                        'data' => [
                            'content' => '<p>Structured body</p>',
                        ],
                        'settings' => [
                            'alignment' => 'left',
                            'background_style' => 'none',
                            'intro_style' => false,
                        ],
                    ],
                ],
            ])
            ->call('save')
            ->assertSet('message', 'Contentblokken opgeslagen.')
            ->assertDispatched('content-block-saved', fn (string $event, array $params): bool => $params === [
                'closeAll' => true,
            ]);

        $contentItem->refresh();

        $this->assertSame('title', $contentItem->structured_blocks[0]['type']);
        $this->assertSame('A structured title', $contentItem->structured_blocks[0]['data']['title']);
        $this->assertSame('text', $contentItem->structured_blocks[1]['type']);
        $this->assertSame('50', $contentItem->structured_blocks[1]['layout']);
    }

    public function test_livewire_content_block_editor_can_save_a_single_block(): void
    {
        $admin = User::factory()->admin()->create();
        $contentItem = ContentItem::query()->create([
            'title' => 'Partial block saves',
            'slug' => 'partial-block-saves',
            'locale' => 'nl',
            'status' => 'draft',
            'structured_blocks' => [
                [
                    'type' => 'title',
                    'uuid' => '2b353495-f7fc-44ab-8baa-16437a232fb6',
                    'layout' => '100',
                    'data' => [
                        'title' => 'Original title',
                        'level' => 'h2',
                    ],
                    'settings' => [
                        'alignment' => 'left',
                    ],
                ],
                [
                    'type' => 'text',
                    'uuid' => 'f11acb28-1ec0-47f7-8f65-a9d3df35d57b',
                    'layout' => '50',
                    'data' => [
                        'content' => '<p>Original body</p>',
                    ],
                    'settings' => [
                        'alignment' => 'left',
                        'background_style' => 'none',
                        'intro_style' => false,
                    ],
                ],
            ],
        ]);

        Livewire::actingAs($admin)
            ->test(ContentBlockEditor::class, [
                'contentItemId' => $contentItem->id,
            ])
            ->set('data.blocks', [
                [
                    'type' => 'title',
                    'data' => [
                        'uuid' => '2b353495-f7fc-44ab-8baa-16437a232fb6',
                        'layout' => '100',
                        'data' => [
                            'title' => 'Unsaved title change',
                            'level' => 'h2',
                        ],
                        'settings' => [
                            'alignment' => 'center',
                        ],
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'uuid' => 'f11acb28-1ec0-47f7-8f65-a9d3df35d57b',
                        'layout' => '75',
                        'data' => [
                            'content' => '<p>Saved body change</p>',
                        ],
                        'settings' => [
                            'alignment' => 'right',
                            'background_style' => 'highlight',
                            'intro_style' => true,
                        ],
                    ],
                ],
            ])
            ->call('saveBlock', 1, [
                'uuid' => 'f11acb28-1ec0-47f7-8f65-a9d3df35d57b',
                'layout' => '75',
                'data' => [
                    'content' => '<p>Saved body change</p>',
                ],
                'settings' => [
                    'alignment' => 'right',
                    'background_style' => 'highlight',
                    'intro_style' => true,
                ],
            ])
            ->assertSet('message', 'Blok opgeslagen.')
            ->assertDispatched('content-block-saved', fn (string $event, array $params): bool => $params === [
                'itemKey' => '1',
                'uuid' => 'f11acb28-1ec0-47f7-8f65-a9d3df35d57b',
            ]);

        $contentItem->refresh();

        $this->assertSame('Original title', $contentItem->structured_blocks[0]['data']['title']);
        $this->assertSame('left', $contentItem->structured_blocks[0]['settings']['alignment']);
        $this->assertSame('<p>Saved body change</p>', $contentItem->structured_blocks[1]['data']['content']);
        $this->assertSame('75', $contentItem->structured_blocks[1]['layout']);
        $this->assertSame('right', $contentItem->structured_blocks[1]['settings']['alignment']);
    }

    public function test_legacy_content_blocks_can_be_migrated_to_structured_blocks(): void
    {
        $contentItem = ContentItem::query()->create([
            'title' => 'Legacy block source',
            'slug' => 'legacy-block-source',
            'status' => 'draft',
        ]);
        $layout = ContentBlockLayout::query()->create([
            'name' => '2 columns',
            'handle' => '2-column',
            'columns' => [50, 50],
        ]);
        $block = ContentBlock::query()->create([
            'content_item_id' => $contentItem->id,
            'layout_id' => $layout->id,
            'name' => 'Legacy row',
            'sort_order' => 1,
        ]);
        $container = ContentBlockPartContainer::query()->create([
            'block_id' => $block->id,
            'region' => 'column_1',
            'sort_order' => 1,
        ]);
        ContentBlockPart::query()->create([
            'container_id' => $container->id,
            'type' => 'youtube',
            'title' => 'Legacy video',
            'content' => 'abc123video',
            'sort_order' => 1,
        ]);

        $this->artisan('cms:migrate-content-blocks')
            ->assertSuccessful();

        $contentItem->refresh();

        $this->assertSame('video', $contentItem->structured_blocks[0]['type']);
        $this->assertSame('50', $contentItem->structured_blocks[0]['layout']);
        $this->assertSame('https://www.youtube.com/watch?v=abc123video', $contentItem->structured_blocks[0]['data']['video_url']);
        $this->assertNotEmpty($contentItem->legacy_block_snapshot);
        $this->assertNotNull($contentItem->legacy_blocks_migrated_at);
    }

    public function test_content_edit_shows_url_field_and_icon_only_live_page_button(): void
    {
        $admin = User::factory()->admin()->create();
        $contentItem = ContentItem::query()->create([
            'title' => 'Public item',
            'slug' => 'public-item',
            'locale' => 'nl',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get("/admin/content/{$contentItem->id}/edit")
            ->assertOk()
            ->assertSee('btn-preview', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('action="'.route('admin.content.preview', ['id' => $contentItem->id]).'"', false)
            ->assertSee('<label for="slug">URL</label>', false)
            ->assertSee('url-input-base', false)
            ->assertSee(rtrim(url('/'), '/').'/', false)
            ->assertSee('value="public-item"', false)
            ->assertSee('btn-live-page', false)
            ->assertSee('aria-label="View live page"', false)
            ->assertSee('href="'.route('frontend.pages.show', ['slug' => 'public-item']).'"', false)
            ->assertSee('>public</span>', false)
            ->assertSee('Blokken toevoegen')
            ->assertSee('content-block-editor', false)
            ->assertSee('Blokken opslaan')
            ->assertDontSee('name="intro"', false)
            ->assertDontSee('name="body"', false)
            ->assertDontSee('<label for="intro">', false)
            ->assertDontSee('<label for="body">', false)
            ->assertDontSee('Twit'.'ter')
            ->assertDontSee('twit'.'terbericht', false)
            ->assertDontSee('twe'.'etit', false);
    }

    public function test_admin_can_add_attachments_when_saving_existing_content_item(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $contentItem = ContentItem::query()->create([
            'title' => 'Existing item',
            'slug' => 'existing-item',
            'locale' => 'nl',
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->post("/admin/content/{$contentItem->id}", [
                'id' => $contentItem->id,
                'title' => 'Existing item',
                'slug' => 'existing-item',
                'locale' => 'nl',
                'status' => 'published',
                'attachment_names' => ['Updated specification'],
                'attachment_files' => [
                    UploadedFile::fake()->create('updated-specification.pdf', 12, 'application/pdf'),
                ],
            ])
            ->assertRedirect("/admin/content/{$contentItem->id}/edit");

        $this->assertDatabaseHas('content_attachments', [
            'content_item_id' => $contentItem->id,
            'name' => 'Updated specification',
            'type' => 'application/pdf',
        ]);

        $attachment = ContentAttachment::query()
            ->where('content_item_id', $contentItem->id)
            ->firstOrFail();

        Storage::disk('public')->assertExists(str_replace('storage/', '', $attachment->url));
    }

    public function test_livewire_attachment_manager_queues_multiple_selections_and_uploads_them(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $contentItem = ContentItem::query()->create([
            'title' => 'Async attachments',
            'slug' => 'async-attachments',
            'locale' => 'nl',
            'status' => 'draft',
        ]);

        Livewire::actingAs($admin)
            ->test(AttachmentManager::class, [
                'module' => 'content',
                'recordId' => $contentItem->id,
            ])
            ->set('incomingUploads', [
                UploadedFile::fake()->create('first-specification.pdf', 12, 'application/pdf'),
            ])
            ->assertSet('queuedNames', ['first-specification'])
            ->set('incomingUploads', [
                UploadedFile::fake()->create('second-specification.pdf', 12, 'application/pdf'),
            ])
            ->assertSet('queuedNames', ['first-specification', 'second-specification'])
            ->set('queuedNames.1', 'Second renamed')
            ->call('uploadAttachments')
            ->assertSet('queuedNames', [])
            ->assertSet('queuedUploads', []);

        $this->assertDatabaseHas('content_attachments', [
            'content_item_id' => $contentItem->id,
            'name' => 'first-specification',
        ]);
        $this->assertDatabaseHas('content_attachments', [
            'content_item_id' => $contentItem->id,
            'name' => 'Second renamed',
        ]);

        ContentAttachment::query()
            ->where('content_item_id', $contentItem->id)
            ->get()
            ->each(fn (ContentAttachment $attachment) => Storage::disk('public')->assertExists(str_replace('storage/', '', $attachment->url)));
    }

    public function test_livewire_attachment_manager_saves_existing_list_changes_without_page_save(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $contentItem = ContentItem::query()->create([
            'title' => 'Managed attachments',
            'slug' => 'managed-attachments',
            'locale' => 'nl',
            'status' => 'draft',
        ]);

        Storage::disk('public')->put('content/attachments/first.pdf', 'first');
        Storage::disk('public')->put('content/attachments/second.pdf', 'second');

        $first = ContentAttachment::query()->create([
            'content_item_id' => $contentItem->id,
            'name' => 'First',
            'type' => 'application/pdf',
            'url' => 'storage/content/attachments/first.pdf',
            'sort_order' => 1,
        ]);
        $second = ContentAttachment::query()->create([
            'content_item_id' => $contentItem->id,
            'name' => 'Second',
            'type' => 'application/pdf',
            'url' => 'storage/content/attachments/second.pdf',
            'sort_order' => 2,
        ]);

        Livewire::actingAs($admin)
            ->test(AttachmentManager::class, [
                'module' => 'content',
                'recordId' => $contentItem->id,
            ])
            ->set("attachmentForms.{$first->id}.name", 'First renamed')
            ->call('saveAttachment', $first->id)
            ->set('draggedAttachmentId', $second->id)
            ->call('moveAttachment', $first->id)
            ->call('deleteAttachment', $first->id);

        $this->assertDatabaseHas('content_attachments', [
            'id' => $first->id,
            'name' => 'First renamed',
        ]);
        $this->assertSoftDeleted('content_attachments', [
            'id' => $first->id,
        ]);
        $this->assertDatabaseHas('content_attachments', [
            'id' => $second->id,
            'sort_order' => 1,
        ]);
        Storage::disk('public')->assertMissing('content/attachments/first.pdf');
    }

    public function test_livewire_attachment_capacity_is_hidden_until_requested(): void
    {
        $admin = User::factory()->admin()->create();
        $contentItem = ContentItem::query()->create([
            'title' => 'Capacity details',
            'slug' => 'capacity-details',
            'locale' => 'nl',
            'status' => 'draft',
        ]);

        Livewire::actingAs($admin)
            ->test(AttachmentManager::class, [
                'module' => 'content',
                'recordId' => $contentItem->id,
            ])
            ->assertSet('capacityVisible', false)
            ->assertDontSee('Bestandsgrootte')
            ->call('toggleCapacity')
            ->assertSet('capacityVisible', true)
            ->assertSee('Bestandsgrootte');
    }

    public function test_content_preview_uses_temporary_ip_bound_noindex_url(): void
    {
        $admin = User::factory()->admin()->create();
        $contentItem = ContentItem::query()->create([
            'title' => 'Draft preview item',
            'slug' => 'draft-preview-item',
            'locale' => 'nl',
            'status' => 'draft',
            'body' => 'Draft body can be checked before publishing.',
        ]);
        $ipAddress = '203.0.113.10';

        $this->get('/draft-preview-item')->assertNotFound();

        $response = $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
            ->actingAs($admin)
            ->post("/admin/content/{$contentItem->id}/preview");

        $response->assertRedirect();

        $location = (string) $response->headers->get('Location');
        $path = (string) parse_url($location, PHP_URL_PATH);

        $this->assertMatchesRegularExpression('#/preview/content/[a-f0-9]{64}$#i', $path);
        $this->assertSame(1, ContentPreviewToken::query()->count());

        $previewToken = ContentPreviewToken::query()->firstOrFail();

        $this->assertSame($contentItem->id, $previewToken->content_item_id);
        $this->assertSame($admin->id, $previewToken->user_id);
        $this->assertSame($ipAddress, $previewToken->ip_address);
        $this->assertTrue($previewToken->expires_at->isFuture());

        $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
            ->get($path)
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive">', false)
            ->assertSee('Draft preview item')
            ->assertSee('Draft body can be checked before publishing.');

        $this->assertSame(1, $previewToken->refresh()->used_count);
        $this->assertNotNull($previewToken->last_used_at);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->get($path)
            ->assertNotFound();
    }

    public function test_content_photo_album_ajax_endpoints_upload_rename_sort_and_delete_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $contentItem = ContentItem::query()->create([
            'title' => 'Photo item',
            'slug' => 'photo-item',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post("/admin/content/ajax/uploadFotoalbumAfbeelding?id={$contentItem->id}", [
                'image' => UploadedFile::fake()->image('hero.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $firstImage = ContentImage::query()->firstOrFail();
        $secondImage = ContentImage::query()->create([
            'content_item_id' => $contentItem->id,
            'image_path' => 'storage/content/images/second.jpg',
            'caption' => 'Second',
            'sort_order' => 2,
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/content/ajax/updateAfbeeldingnaam', [
                'uploadId' => $firstImage->id,
                'uploadName' => 'Renamed hero',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('content_images', [
            'id' => $firstImage->id,
            'caption' => 'Renamed hero',
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/content/ajax/updateSortIndex', [
                'sort_index' => "{$secondImage->id},{$firstImage->id}",
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('content_images', [
            'id' => $secondImage->id,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/content/ajax/deleteAfbeelding', [
                'id' => $firstImage->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSoftDeleted('content_images', [
            'id' => $firstImage->id,
        ]);
    }

    public function test_content_listing_overview_filters_on_demand_and_sorts_asynchronously(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ContentCategory::query()->create([
            'name' => 'News',
            'slug' => 'news',
            'status' => 'active',
        ]);
        $otherCategory = ContentCategory::query()->create([
            'name' => 'Other',
            'slug' => 'other',
            'status' => 'active',
        ]);
        $childCategory = ContentCategory::query()->create([
            'name' => 'Child news',
            'slug' => 'child-news',
            'parent_id' => $category->id,
            'status' => 'active',
        ]);

        $alpha = ContentItem::query()->create([
            'title' => 'Aardvark release',
            'slug' => 'aardvark-release',
            'locale' => 'nl',
            'status' => 'published',
        ]);
        $zebra = ContentItem::query()->create([
            'title' => 'Zebra update',
            'slug' => 'zebra-update',
            'locale' => 'en',
            'status' => 'draft',
        ]);
        $other = ContentItem::query()->create([
            'title' => 'Other item',
            'slug' => 'other-item',
            'locale' => 'nl',
            'status' => 'published',
        ]);
        $child = ContentItem::query()->create([
            'title' => 'Nested category item',
            'slug' => 'nested-category-item',
            'locale' => 'nl',
            'status' => 'published',
        ]);

        $alpha->categories()->sync([$category->id => ['sort_order' => 1]]);
        $zebra->categories()->sync([$category->id => ['sort_order' => 2]]);
        $other->categories()->sync([$otherCategory->id => ['sort_order' => 1]]);
        $child->categories()->sync([$childCategory->id => ['sort_order' => 1]]);

        Livewire::actingAs($admin)
            ->test(ListingOverview::class, ['module' => 'content'])
            ->assertSee('Aardvark release')
            ->assertSee('Zebra update')
            ->assertSee('language-flag', false)
            ->call('openCategorySelector')
            ->assertSet('categorySelectorOpen', true)
            ->assertSee('News')
            ->assertSee('Other')
            ->assertSee('Ook onderliggende')
            ->call('selectCategory', $category->id)
            ->assertSet('draftCategoryId', $category->id)
            ->assertSet('filterCategoryId', $category->id)
            ->assertSet('categorySelectorOpen', false)
            ->assertSee('Aardvark release')
            ->assertSee('Zebra update')
            ->assertDontSee('Nested category item')
            ->assertDontSee('Other item')
            ->call('openCategorySelector')
            ->set('draftShowChild', true)
            ->assertSet('showChild', true)
            ->assertSee('Nested category item')
            ->assertDontSee('Other item')
            ->call('resetFilters')
            ->set('draftTitle', 'Aardvark')
            ->assertSee('Zebra update')
            ->call('applyFilters')
            ->assertSee('Aardvark release')
            ->assertDontSee('Zebra update')
            ->call('resetFilters')
            ->call('sortBy', 'title', 'asc')
            ->assertSeeInOrder(['Aardvark release', 'Zebra update'])
            ->call('sortBy', 'title', 'desc')
            ->assertSeeInOrder(['Zebra update', 'Aardvark release']);
    }

    public function test_content_categories_use_reusable_livewire_tree_with_details_and_drag_sorting(): void
    {
        $admin = User::factory()->admin()->create();
        $news = ContentCategory::query()->create([
            'name' => 'News',
            'slug' => 'news',
            'status' => 'active',
            'sort_order' => 1,
        ]);
        $archive = ContentCategory::query()->create([
            'name' => 'Archive',
            'slug' => 'archive',
            'status' => 'inactive',
            'sort_order' => 2,
        ]);
        $contentItem = ContentItem::query()->create([
            'title' => 'Linked category article',
            'slug' => 'linked-category-article',
            'status' => 'published',
        ]);
        $contentItem->categories()->sync([$news->id => ['sort_order' => 1]]);

        Livewire::actingAs($admin)
            ->test(CategoryTreeManager::class, ['module' => 'content'])
            ->assertSee('News')
            ->assertSee('Archive')
            ->assertSee('category-tree-status active-item', false)
            ->assertSee('category-tree-status inactive-item', false)
            ->call('selectCategory', $news->id)
            ->assertSet('selectedCategoryId', $news->id)
            ->assertSee(url('/news'))
            ->assertSee('Linked category article')
            ->assertSee('Totaal gekoppeld')
            ->assertSee('1')
            ->set('draggedCategoryId', $archive->id)
            ->call('moveCategory', $news->id)
            ->assertSee('Categorie volgorde opgeslagen.');

        $this->assertSame(1, $archive->refresh()->sort_order);
        $this->assertSame(2, $news->refresh()->sort_order);
    }

    public function test_content_photo_album_livewire_component_uploads_sorts_and_saves_image_seo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $contentItem = ContentItem::query()->create([
            'title' => 'Livewire album item',
            'slug' => 'livewire-album-item',
            'status' => 'published',
        ]);

        Livewire::actingAs($admin)
            ->test(ContentImageAlbum::class, ['contentItem' => $contentItem])
            ->assertSee('Bestanden voorbereiden')
            ->assertSee('De afbeeldingen worden gecontroleerd. Dit kan even duren bij een grote batch.')
            ->assertSee('Afbeeldingen opslaan')
            ->assertSee('De bestanden worden opgeslagen en aan het fotoalbum toegevoegd.')
            ->assertSet('capacityVisible', false)
            ->assertDontSee('Bestandsgrootte')
            ->call('toggleCapacity')
            ->assertSet('capacityVisible', true)
            ->assertSee('Bestandsgrootte')
            ->set('uploads', [
                UploadedFile::fake()->image('blue-hero.jpg'),
                UploadedFile::fake()->image('detail-shot.png'),
            ])
            ->call('uploadImages')
            ->assertSee('Images uploaded.');

        $firstImage = ContentImage::query()->where('content_item_id', $contentItem->id)->orderBy('sort_order')->firstOrFail();
        $secondImage = ContentImage::query()->where('content_item_id', $contentItem->id)->orderByDesc('sort_order')->firstOrFail();

        $this->assertDatabaseHas('content_images', [
            'id' => $firstImage->id,
            'caption' => 'Blue Hero',
            'alt_text' => 'Blue Hero',
            'title_text' => 'Blue Hero',
            'original_filename' => 'blue-hero.jpg',
        ]);

        Livewire::actingAs($admin)
            ->test(ContentImageAlbum::class, ['contentItem' => $contentItem])
            ->assertSee('Afbeeldingen')
            ->assertDontSee('Reeds gekoppelde afbeeldingen')
            ->assertDontSee('content-album-editor-title', false)
            ->call('editImage', $firstImage->id)
            ->assertSet('editingImageId', $firstImage->id)
            ->assertSee('content-album-editor-title', false)
            ->set("imageForms.{$firstImage->id}.caption", 'Homepage hero')
            ->set("imageForms.{$firstImage->id}.alt_text", 'Blue product hero image')
            ->set("imageForms.{$firstImage->id}.title_text", 'Product hero')
            ->set("imageForms.{$firstImage->id}.description", 'A detailed SEO description for the image.')
            ->set("imageForms.{$firstImage->id}.credit", 'Studio team')
            ->call('saveImage', $firstImage->id)
            ->assertSet('editingImageId', null)
            ->call('moveImage', $firstImage->id, $secondImage->id, 'before')
            ->assertSee('flash-message-success', false)
            ->assertSee('Image order saved.');

        $this->assertDatabaseHas('content_images', [
            'id' => $firstImage->id,
            'caption' => 'Homepage hero',
            'alt_text' => 'Blue product hero image',
            'title_text' => 'Product hero',
            'description' => 'A detailed SEO description for the image.',
            'credit' => 'Studio team',
            'sort_order' => 2,
        ]);

        $this->assertDatabaseHas('content_images', [
            'id' => $secondImage->id,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($admin)
            ->test(ContentImageAlbum::class, ['contentItem' => $contentItem])
            ->call('moveImage', $firstImage->id, $secondImage->id, 'after')
            ->assertSee('Image order saved.');

        $this->assertDatabaseHas('content_images', [
            'id' => $firstImage->id,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('content_images', [
            'id' => $secondImage->id,
            'sort_order' => 2,
        ]);
    }

    public function test_content_photo_album_upload_validation_messages_follow_active_locale(): void
    {
        app()->setLocale('nl');
        app('translator')->setLoaded([]);

        $validator = Validator::make(
            ['uploads' => [UploadedFile::fake()->create('notes.txt', 1, 'text/plain')]],
            ['uploads' => ['array', 'max:20'], 'uploads.*' => ['image', 'max:20480']],
        );

        $this->assertFalse($validator->passes());
        $this->assertSame('Elke upload moet een afbeelding zijn.', $validator->errors()->first('uploads.0'));

        $failedUpload = new UploadedFile(__FILE__, 'too-large.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);
        $validator = Validator::make(
            ['uploads' => [$failedUpload]],
            ['uploads.*' => ['file']],
        );

        $this->assertFalse($validator->passes());
        $this->assertSame(
            'Een afbeelding kon niet worden geupload. Controleer de bestandsgrootte en probeer opnieuw.',
            $validator->errors()->first('uploads.0'),
        );

        $failedUpload = new UploadedFile(__FILE__, 'too-large.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);
        $validator = Validator::make(
            ['files' => [$failedUpload]],
            ['files.*' => ['file']],
        );

        $this->assertFalse($validator->passes());
        $this->assertSame(
            'Een bestand kon niet worden geupload. Controleer de bestandsgrootte en probeer opnieuw.',
            $validator->errors()->first('files.0'),
        );

        app()->setLocale('en');
        app('translator')->setLoaded([]);

        $validator = Validator::make(
            ['uploads' => [UploadedFile::fake()->create('notes.txt', 1, 'text/plain')]],
            ['uploads' => ['array', 'max:20'], 'uploads.*' => ['image', 'max:20480']],
        );

        $this->assertFalse($validator->passes());
        $this->assertSame('Every upload must be an image.', $validator->errors()->first('uploads.0'));

        $failedUpload = new UploadedFile(__FILE__, 'too-large.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);
        $validator = Validator::make(
            ['uploads' => [$failedUpload]],
            ['uploads.*' => ['file']],
        );

        $this->assertFalse($validator->passes());
        $this->assertSame(
            'An image could not be uploaded. Check the file size and try again.',
            $validator->errors()->first('uploads.0'),
        );

        $failedUpload = new UploadedFile(__FILE__, 'too-large.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);
        $validator = Validator::make(
            ['files' => [$failedUpload]],
            ['files.*' => ['file']],
        );

        $this->assertFalse($validator->passes());
        $this->assertSame(
            'A file could not be uploaded. Check the file size and try again.',
            $validator->errors()->first('files.0'),
        );
    }

    public function test_content_items_can_be_duplicated_with_media_attachments_categories_and_structured_blocks(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ContentCategory::query()->create([
            'name' => 'Updates',
            'slug' => 'updates',
            'status' => 'active',
        ]);
        $contentItem = ContentItem::query()->create([
            'title' => 'Original item',
            'slug' => 'original-item',
            'status' => 'published',
            'structured_blocks' => [
                [
                    'type' => 'video',
                    'uuid' => '2b353495-f7fc-44ab-8baa-16437a232fb6',
                    'layout' => '100',
                    'data' => [
                        'video_url' => 'https://www.youtube.com/watch?v=abc123video',
                    ],
                    'settings' => [
                        'provider' => 'youtube',
                    ],
                ],
            ],
        ]);
        $contentItem->categories()->sync([$category->id => ['sort_order' => 1]]);

        ContentAttachment::query()->create([
            'content_item_id' => $contentItem->id,
            'name' => 'Original attachment',
            'url' => 'storage/content/attachments/original.pdf',
            'sort_order' => 1,
        ]);
        ContentImage::query()->create([
            'content_item_id' => $contentItem->id,
            'image_path' => 'storage/content/images/original.jpg',
            'caption' => 'Original image',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/content/ajax/duplicateItem', [
                'itemId' => $contentItem->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $copy = ContentItem::query()->whereKeyNot($contentItem->id)->firstOrFail();

        $this->assertSame('draft', $copy->status);
        $this->assertDatabaseHas('content_category_content_item', [
            'content_category_id' => $category->id,
            'content_item_id' => $copy->id,
        ]);
        $this->assertDatabaseHas('content_attachments', [
            'content_item_id' => $copy->id,
            'name' => 'Original attachment',
        ]);
        $this->assertDatabaseHas('content_images', [
            'content_item_id' => $copy->id,
            'caption' => 'Original image',
        ]);
        $this->assertSame('video', $copy->structured_blocks[0]['type']);
        $this->assertSame('https://www.youtube.com/watch?v=abc123video', $copy->structured_blocks[0]['data']['video_url']);
        $this->assertNotSame($contentItem->structured_blocks[0]['uuid'], $copy->structured_blocks[0]['uuid']);
    }

    public function test_content_categories_support_legacy_fields_and_image_actions(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/content/categorieen/edit', [
                'naam' => 'Legacy category fields',
                'slug' => 'legacy-category-fields',
                'content' => 'Category body',
                'metadescription' => 'Category meta',
                'custom_url' => '/custom-category',
                'actief' => 1,
                'images' => [
                    UploadedFile::fake()->image('category.jpg'),
                ],
                'image_captions' => ['Category image'],
            ])
            ->assertRedirect('/admin/content/categorieen/1/edit');

        $this->assertDatabaseHas('content_categories', [
            'name' => 'Legacy category fields',
            'description' => 'Category body',
            'meta_description' => 'Category meta',
            'custom_url' => '/custom-category',
            'status' => 'active',
        ]);

        $image = ContentCategoryImage::query()->firstOrFail();

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/content/categorieen/ajax/updateAfbeeldingnaam', [
                'uploadId' => $image->id,
                'uploadName' => 'Updated category image',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('content_category_images', [
            'id' => $image->id,
            'caption' => 'Updated category image',
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/content/categorieen/ajax/deleteAfbeelding', [
                'id' => $image->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSoftDeleted('content_category_images', [
            'id' => $image->id,
        ]);
    }
}
