<?php

namespace Tests\Feature\Admin;

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
use Tests\TestCase;

class FormBuilderModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_module_seeder_creates_builder_structure_without_duplicates(): void
    {
        $this->seed(FormModuleSeeder::class);
        $this->seed(FormModuleSeeder::class);

        $this->assertSame(1, FormCategory::query()->count());
        $this->assertSame(1, Form::query()->count());
        $this->assertSame(2, FormRecipient::query()->count());
        $this->assertSame(1, FormBlock::query()->count());
        $this->assertSame(4, FormField::query()->count());
        $this->assertSame(3, FormFieldOption::query()->count());

        $this->assertDatabaseHas('forms', [
            'slug' => 'seeded-contact-form',
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
            ->assertSee('Leads');
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
            ->assertRedirect('/admin/form/1/edit?tab=builder');

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

    public function test_public_form_submission_is_validated_stored_and_emailed(): void
    {
        Mail::fake();
        $this->seed(FormModuleSeeder::class);

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
            ->delete('/admin/form/categorieen/1')
            ->assertRedirect('/admin/form/categorieen');

        $this->assertSoftDeleted('form_categories', [
            'id' => 1,
        ]);
    }
}
