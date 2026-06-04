<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Forms\ResponseMailBuilder;
use App\Mail\FormSubmissionNotification;
use App\Models\User;
use App\Models\Cms\Form;
use App\Models\Cms\FormBlock;
use App\Models\Cms\FormCategory;
use App\Models\Cms\FormField;
use App\Models\Cms\FormFieldOption;
use App\Models\Cms\FormRecipient;
use App\Models\Cms\FormSubmission;
use Database\Seeders\FormModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class FormBuilderModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_module_seeder_creates_builder_structure_without_duplicates(): void
    {
        $this->seed(FormModuleSeeder::class);
        $this->seed(FormModuleSeeder::class);

        $this->assertSame(1, FormCategory::query()->count());
        $this->assertSame(2, Form::query()->count());
        $this->assertSame(4, FormRecipient::query()->count());
        $this->assertSame(2, FormBlock::query()->count());
        $this->assertSame(8, FormField::query()->count());
        $this->assertSame(6, FormFieldOption::query()->count());

        $this->assertDatabaseHas('forms', [
            'slug' => 'seeded-contact-form',
            'locale' => 'nl',
            'name' => 'Voorbeeld contactformulier',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('forms', [
            'slug' => 'seeded-contact-form-en',
            'locale' => 'en',
            'name' => 'Seeded contact form',
            'status' => 'published',
        ]);
    }

    public function test_admin_can_create_form_with_multiple_recipients_and_mail_layouts(): void
    {
        $admin = User::factory()->admin()->create();
        $category = FormCategory::query()->create([
            'name' => 'Leads',
            'slug' => 'leads',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/form/edit', [
                'name' => 'Quote request',
                'slug' => 'quote-request',
                'locale' => 'nl',
                'status' => '1',
                'description' => 'Lead capture form',
                'submit_text' => 'Send',
                'success_message' => 'Thanks',
                'categorie' => [$category->id],
                'show_title' => '1',
                'store_submissions' => '1',
                'honeypot_enabled' => '1',
                'recipients' => [
                    ['name' => 'Office', 'email' => 'office@example.com', 'type' => 'to', 'is_active' => '1'],
                    ['name' => 'Sales', 'email' => 'sales@example.com', 'type' => 'cc', 'is_active' => '1'],
                ],
                'messages' => [
                    ['name' => 'Notify', 'subject' => 'New quote from {name}', 'body' => '{{summary}}', 'type' => 'notification', 'is_active' => '1', 'layout' => 'default'],
                    ['name' => 'Confirm', 'subject' => 'Thanks', 'body' => 'Thanks {name}', 'type' => 'confirmation', 'is_active' => '1', 'layout' => 'compact'],
                ],
            ])
            ->assertRedirect('/admin/form/1/edit');

        $this->assertDatabaseHas('forms', [
            'name' => 'Quote request',
            'slug' => 'quote-request',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('form_category_form', [
            'form_category_id' => $category->id,
            'form_id' => 1,
        ]);
        $this->assertDatabaseHas('form_recipients', [
            'form_id' => 1,
            'email' => 'sales@example.com',
            'type' => 'cc',
        ]);
        $this->assertDatabaseHas('form_messages', [
            'form_id' => 1,
            'type' => 'confirmation',
            'subject' => 'Thanks',
        ]);

        $this->actingAs($admin)
            ->get('/admin/form')
            ->assertOk()
            ->assertSee('Quote request')
            ->assertSee('Leads')
            ->assertSee('name="locale"', false)
            ->assertSee('vendor/flag-icons/flags/4x3/nl.svg', false)
            ->assertDontSee('ajax/duplicateItem', false)
            ->assertDontSee('Dupliceren');
    }

    public function test_admin_can_save_builder_fields_options_and_duplicate_form(): void
    {
        $admin = User::factory()->admin()->create();
        $form = Form::query()->create([
            'name' => 'Builder test',
            'slug' => 'builder-test',
            'status' => 'published',
        ]);
        $block = $form->blocks()->create(['title' => 'General', 'sort_order' => 1]);
        $row = $block->rows()->create(['sort_order' => 1]);

        $this->actingAs($admin)
            ->post('/admin/form/builder', [
                'id' => $form->id,
                'blocks' => [
                    [
                        'id' => $block->id,
                        'title' => 'General details',
                        'sort_order' => 1,
                        'rows' => [
                            [
                                'id' => $row->id,
                                'sort_order' => 1,
                                'fields' => [
                                    [
                                        'name' => 'interest',
                                        'label' => 'Interest',
                                        'type' => 'select',
                                        'is_required' => '1',
                                        'sort_order' => 1,
                                        'options' => [
                                            ['label' => 'Website', 'value' => 'website'],
                                            ['label' => 'Support', 'value' => 'support'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect('/admin/form/1/edit/builder');

        $this->assertDatabaseHas('form_fields', [
            'row_id' => $row->id,
            'name' => 'interest',
            'label' => 'Interest',
            'type' => 'select',
            'is_required' => true,
        ]);
        $this->assertDatabaseHas('form_field_options', [
            'label' => 'Website',
            'value' => 'website',
        ]);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/form/ajax/duplicateItem', [
                'itemId' => $form->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $copy = Form::query()->where('slug', 'like', 'builder-test-copy-%')->firstOrFail();

        $this->assertSame('draft', $copy->status);
        $this->assertDatabaseHas('form_fields', [
            'name' => 'interest',
            'label' => 'Interest',
        ]);
        $this->assertSame(2, Form::query()->count());
        $this->assertSame(2, FormFieldOption::query()->where('label', 'Website')->count());
    }

    public function test_form_builder_renders_as_separate_palette_canvas_tab(): void
    {
        $admin = User::factory()->admin()->create();
        $form = Form::query()->create([
            'name' => 'Contact builder',
            'slug' => 'contact-builder',
            'status' => 'published',
        ]);
        $block = $form->blocks()->create(['title' => 'General', 'sort_order' => 1]);
        $row = $block->rows()->create(['sort_order' => 1]);
        $row->fields()->create([
            'name' => 'email',
            'label' => 'Email address',
            'type' => 'email',
            'sort_order' => 1,
        ]);

        $editResponse = $this->actingAs($admin)
            ->get("/admin/form/{$form->id}/edit")
            ->assertOk()
            ->assertSee('form-general-form', false)
            ->assertSee('Tekst op verzendknop')
            ->assertSee('Bericht na succesvol verzenden formulier')
            ->assertDontSee('<h2 class="title">Algemeen</h2>', false)
            ->assertDontSee('<label for="slug">Slug</label>', false)
            ->assertDontSee('id="slug"', false)
            ->assertDontSee('data-form-builder', false)
            ->assertDontSee('form-builder-canvas', false);

        preg_match('/<div class="item-tabs-container">(.*?)<\/div>/s', $editResponse->getContent(), $tabMenu);
        $this->assertNotEmpty($tabMenu);
        $this->assertMatchesRegularExpression(
            '/Algemeen.*Formulier.*Ontvangers.*Bevestigingsmail.*Ontvangen berichten/s',
            $tabMenu[1],
        );
        $this->assertStringNotContainsString('Template', $tabMenu[1]);
        $this->assertStringNotContainsString('Form builder', $tabMenu[1]);
        $this->assertStringNotContainsString('Response mail builder', $tabMenu[1]);
        $this->assertStringNotContainsString('Inzendingen', $tabMenu[1]);

        $this->actingAs($admin)
            ->get("/admin/form/{$form->id}/edit?tab=builder")
            ->assertRedirect("/admin/form/{$form->id}/edit/builder");

        $this->actingAs($admin)
            ->get("/admin/form/{$form->id}/edit/builder")
            ->assertOk()
            ->assertSee('data-form-builder', false)
            ->assertSee('form-builder-palette', false)
            ->assertSee('form-builder-canvas', false)
            ->assertSee('form-builder-modal', false)
            ->assertSee('form="form-builder-form"', false)
            ->assertSee('Email address')
            ->assertDontSee('form-general-form', false)
            ->assertDontSee('Blok titel')
            ->assertDontSee('Nieuwe rij');
    }

    public function test_form_edit_template_recipients_and_response_tabs_render_modern_sections(): void
    {
        $admin = User::factory()->admin()->create();
        $form = Form::query()->create([
            'name' => 'Contact tabs',
            'slug' => 'contact-tabs',
            'status' => 'published',
            'settings' => [
                'layout' => 'default',
                'mail_template' => 'mail.forms.submission',
                'honeypot_enabled' => true,
                'honeypot_field' => 'legacy_field',
            ],
        ]);

        $form->recipients()->create([
            'name' => 'Office',
            'email' => 'office@example.com',
            'type' => 'to',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $form->messages()->create([
            'name' => 'Confirm',
            'subject' => 'Thanks',
            'body' => 'We received it.',
            'type' => 'confirmation',
            'is_active' => true,
            'sort_order' => 1,
            'settings' => ['layout' => 'default', 'preheader' => 'Legacy preheader'],
        ]);
        $block = $form->blocks()->create(['title' => 'General', 'sort_order' => 1]);
        $row = $block->rows()->create(['sort_order' => 1]);
        $row->fields()->create([
            'name' => 'email',
            'label' => 'Email address',
            'type' => 'email',
            'sort_order' => 1,
        ]);

        $templateResponse = $this->actingAs($admin)
            ->get("/admin/form/{$form->id}/edit/template")
            ->assertOk()
            ->assertSee('Template module openen')
            ->assertSee('mail.forms.submission')
            ->assertDontSee('<h2 class="title">Template</h2>', false)
            ->assertDontSee('honeypot_enabled', false)
            ->assertDontSee('honeypot_field', false);

        $this->assertSame(1, substr_count($templateResponse->getContent(), 'class="main-section"'));

        $recipientsResponse = $this->actingAs($admin)
            ->get("/admin/form/{$form->id}/edit/recipients")
            ->assertOk()
            ->assertSee('data-form-managed-list="recipients"', false)
            ->assertSee('form-section-toolbar-actions-only', false)
            ->assertSee('Regel toevoegen')
            ->assertSee('Office')
            ->assertDontSee('<h2 class="title">Ontvangers</h2>', false)
            ->assertSee('btn-remove', false);

        $this->assertSame(1, substr_count($recipientsResponse->getContent(), 'class="main-section"'));

        $this->actingAs($admin)
            ->get("/admin/form/{$form->id}/edit/response")
            ->assertOk()
            ->assertSee('Bevestigingsmail')
            ->assertSee('data-response-mail-builder', false)
            ->assertSee('form-response-mail-builder-form', false)
            ->assertSee('data-mail-placeholder-token="{form_name}"', false)
            ->assertSee('data-mail-placeholder-token="{email}"', false)
            ->assertDontSee('data-form-managed-list="messages"', false)
            ->assertDontSee('form-general-form', false)
            ->assertDontSee('Mail layout builder')
            ->assertDontSee('Preheader')
            ->assertDontSee('messages[0][preheader]', false);
    }

    public function test_livewire_response_mail_builder_saves_messages_and_renders_clickable_tags(): void
    {
        $admin = User::factory()->admin()->create();
        $form = Form::query()->create([
            'name' => 'Live response form',
            'slug' => 'live-response-form',
            'status' => 'published',
        ]);
        $block = $form->blocks()->create(['title' => 'General', 'sort_order' => 1]);
        $row = $block->rows()->create(['sort_order' => 1]);
        $row->fields()->create([
            'name' => 'name',
            'label' => 'Name',
            'type' => 'input',
            'sort_order' => 1,
        ]);
        $message = $form->messages()->create([
            'name' => 'Confirm',
            'subject' => 'Thanks',
            'body' => 'Original body',
            'type' => 'confirmation',
            'is_active' => true,
            'sort_order' => 1,
            'settings' => ['layout' => 'default'],
        ]);

        Livewire::actingAs($admin)
            ->test(ResponseMailBuilder::class, ['formId' => $form->id])
            ->assertSee('data-mail-placeholder-token="{name}"', false)
            ->set('messages.0.subject', 'Thanks {name}')
            ->set('messages.0.body', 'Hi {name}, {{summary}}')
            ->set('messages.0.layout', 'compact')
            ->call('addMessage')
            ->set('messages.1.name', 'Follow up')
            ->set('messages.1.subject', 'Follow up {form_name}')
            ->set('messages.1.body', 'Second mail body')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('message', 'Response mail saved.');

        $this->assertDatabaseHas('form_messages', [
            'id' => $message->id,
            'subject' => 'Thanks {name}',
            'body' => 'Hi {name}, {{summary}}',
        ]);
        $this->assertSame('compact', $message->fresh()->settings['layout']);
        $this->assertDatabaseHas('form_messages', [
            'form_id' => $form->id,
            'name' => 'Follow up',
            'subject' => 'Follow up {form_name}',
            'body' => 'Second mail body',
            'type' => 'confirmation',
        ]);
    }

    public function test_form_template_tab_save_preserves_unposted_relations_and_cleans_spam_settings(): void
    {
        $admin = User::factory()->admin()->create();
        $category = FormCategory::query()->create([
            'name' => 'Leads',
            'slug' => 'leads',
            'status' => 'active',
        ]);
        $form = Form::query()->create([
            'name' => 'Settings form',
            'slug' => 'settings-form',
            'locale' => 'nl',
            'status' => 'published',
            'settings' => [
                'layout' => 'default',
                'mail_template' => 'forms.default',
                'store_submissions' => true,
                'honeypot_enabled' => true,
                'honeypot_field' => 'website',
            ],
        ]);

        $form->categories()->sync([$category->id => ['sort_order' => 1]]);
        $form->recipients()->create([
            'name' => 'Office',
            'email' => 'office@example.com',
            'type' => 'to',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $form->messages()->create([
            'name' => 'Notify',
            'subject' => 'New message',
            'body' => '{{summary}}',
            'type' => 'notification',
            'is_active' => true,
            'sort_order' => 1,
            'settings' => ['layout' => 'default'],
        ]);

        $this->actingAs($admin)
            ->post("/admin/form/{$form->id}", [
                'id' => $form->id,
                'active_tab' => 'template',
                'name' => 'Settings form',
                'slug' => 'settings-form',
                'locale' => 'nl',
                'status' => 'published',
                'layout' => 'compact',
                'mail_template' => 'mail.forms.submission',
                'store_submissions' => '1',
                'show_title' => '1',
                'confirmation_email_field' => 'email',
                'from_email' => 'noreply@example.com',
                'from_name' => 'Website',
            ])
            ->assertRedirect("/admin/form/{$form->id}/edit/template");

        $form->refresh();

        $this->assertSame(1, $form->categories()->count());
        $this->assertSame(1, $form->recipients()->count());
        $this->assertSame(1, $form->messages()->count());
        $this->assertSame('compact', $form->settings['layout']);
        $this->assertSame('mail.forms.submission', $form->settings['mail_template']);
        $this->assertArrayNotHasKey('honeypot_enabled', $form->settings);
        $this->assertArrayNotHasKey('honeypot_field', $form->settings);
    }

    public function test_builder_save_is_canonical_for_moved_and_removed_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $form = Form::query()->create([
            'name' => 'Canonical builder',
            'slug' => 'canonical-builder',
            'status' => 'published',
        ]);
        $firstBlock = $form->blocks()->create(['title' => 'General', 'sort_order' => 1]);
        $firstRow = $firstBlock->rows()->create(['sort_order' => 1]);
        $removedField = $firstRow->fields()->create([
            'name' => 'remove_me',
            'label' => 'Remove me',
            'type' => 'input',
            'sort_order' => 1,
        ]);
        $secondBlock = $form->blocks()->create(['title' => 'Other', 'sort_order' => 2]);
        $secondRow = $secondBlock->rows()->create(['sort_order' => 1]);
        $movedField = $secondRow->fields()->create([
            'name' => 'interest',
            'label' => 'Interest',
            'type' => 'select',
            'sort_order' => 1,
        ]);
        $keptOption = $movedField->options()->create([
            'label' => 'Website',
            'value' => 'website',
            'sort_order' => 1,
        ]);
        $removedOption = $movedField->options()->create([
            'label' => 'Support',
            'value' => 'support',
            'sort_order' => 2,
        ]);

        $this->actingAs($admin)
            ->post('/admin/form/builder', [
                'id' => $form->id,
                'blocks' => [
                    [
                        'id' => $firstBlock->id,
                        'title' => 'General',
                        'sort_order' => 1,
                        'rows' => [
                            [
                                'id' => $firstRow->id,
                                'sort_order' => 1,
                                'fields' => [
                                    [
                                        'id' => $movedField->id,
                                        'name' => 'interest',
                                        'label' => 'Primary interest',
                                        'type' => 'select',
                                        'sort_order' => 1,
                                        'options' => [
                                            [
                                                'id' => $keptOption->id,
                                                'label' => 'Website',
                                                'value' => 'website',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect('/admin/form/1/edit/builder');

        $this->assertDatabaseHas('form_fields', [
            'id' => $movedField->id,
            'row_id' => $firstRow->id,
            'label' => 'Primary interest',
        ]);
        $this->assertDatabaseMissing('form_fields', [
            'id' => $removedField->id,
        ]);
        $this->assertDatabaseMissing('form_blocks', [
            'id' => $secondBlock->id,
        ]);
        $this->assertDatabaseHas('form_field_options', [
            'id' => $keptOption->id,
            'label' => 'Website',
        ]);
        $this->assertDatabaseMissing('form_field_options', [
            'id' => $removedOption->id,
        ]);
    }

    public function test_public_form_submission_is_validated_stored_and_emailed(): void
    {
        Mail::fake();
        $this->seed(FormModuleSeeder::class);

        $this->post('/forms/seeded-contact-form', [
            'name' => 'Spam Bot',
            'email' => 'spam@example.com',
            'subject' => 'question',
            'message' => 'This should be blocked.',
            'website' => 'https://spam.example',
        ])
            ->assertSessionHasErrors('website');

        $this->assertSame(0, FormSubmission::query()->count());
        Mail::assertNothingSent();

        $this->post('/forms/seeded-contact-form', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'subject' => 'question',
            'message' => 'Can this form store messages?',
        ])
            ->assertRedirect();

        $this->assertSame(1, FormSubmission::query()->count());
        $this->assertDatabaseHas('form_submission_answers', [
            'field_name' => 'email',
            'value' => 'ada@example.com',
        ]);
        $this->assertDatabaseHas('form_submission_answers', [
            'field_name' => 'subject',
            'value' => 'question',
        ]);

        Mail::assertSent(FormSubmissionNotification::class, 2);
    }

    public function test_form_categories_support_legacy_fields_and_delete_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/form/categorieen/edit', [
                'naam' => 'Legacy form category',
                'slug' => 'legacy-form-category',
                'omschrijving' => 'Category body',
                'status' => 1,
            ])
            ->assertRedirect('/admin/form/categorieen/1/edit');

        $this->assertDatabaseHas('form_categories', [
            'name' => 'Legacy form category',
            'description' => 'Category body',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/admin/form/categorieen/1/edit')
            ->assertOk()
            ->assertDontSee('<label for="slug">Slug</label>', false)
            ->assertDontSee('id="slug"', false);

        $this->actingAs($admin)
            ->delete('/admin/form/categorieen/1')
            ->assertRedirect('/admin/form/categorieen');

        $this->assertSoftDeleted('form_categories', [
            'id' => 1,
        ]);
    }
}
