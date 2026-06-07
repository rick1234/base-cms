<?php

namespace Tests\Feature\Frontend;

use App\Mail\FormSubmissionNotification;
use App\Models\Cms\CmsLanguage;
use App\Models\Cms\Form;
use App\Models\Cms\FormCategory;
use Database\Seeders\CountryLanguageSeeder;
use Database\Seeders\TranslationModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FormRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_forms_overview_renders_published_forms_and_filters_by_category(): void
    {
        $contact = FormCategory::query()->create([
            'name' => 'Contact',
            'slug' => 'contact',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $service = FormCategory::query()->create([
            'name' => 'Service',
            'slug' => 'service',
            'status' => 'active',
            'sort_order' => 2,
        ]);

        $contactForm = Form::query()->create([
            'name' => 'Contact form',
            'slug' => 'contact-form',
            'locale' => 'nl',
            'description' => 'Ask us anything.',
            'status' => 'published',
            'sort_order' => 1,
            'settings' => ['show_title' => true],
        ]);
        $contactForm->categories()->attach($contact->id, ['sort_order' => 1]);

        $serviceForm = Form::query()->create([
            'name' => 'Service form',
            'slug' => 'service-form',
            'locale' => 'nl',
            'description' => 'Request service.',
            'status' => 'published',
            'sort_order' => 2,
        ]);
        $serviceForm->categories()->attach($service->id, ['sort_order' => 1]);

        Form::query()->create([
            'name' => 'Draft form',
            'slug' => 'draft-form',
            'locale' => 'nl',
            'status' => 'draft',
        ]);

        $this->get('/forms')
            ->assertOk()
            ->assertSee('Contact form')
            ->assertSee('Ask us anything.')
            ->assertSee('Service form')
            ->assertDontSee('Draft form')
            ->assertSee('href="'.route('frontend.forms.show', ['form' => 'contact-form']).'"', false)
            ->assertSee('category=contact', false);

        $this->get('/forms?category=contact')
            ->assertOk()
            ->assertSee('Contact form')
            ->assertDontSee('Service form');
    }

    public function test_form_detail_renders_builder_fields_and_seo_metadata(): void
    {
        $category = FormCategory::query()->create([
            'name' => 'Contact',
            'slug' => 'contact',
            'status' => 'active',
        ]);
        $form = $this->formWithFields([
            'name' => 'Public contact',
            'slug' => 'public-contact',
            'description' => 'Use this form to reach us.',
        ]);
        $form->categories()->attach($category->id, ['sort_order' => 1]);

        $this->get('/forms/public-contact')
            ->assertOk()
            ->assertSee('Public contact')
            ->assertSee('Use this form to reach us.')
            ->assertSee('Your name')
            ->assertSee('E-mail')
            ->assertSee('Message')
            ->assertSee('form-builder-honeypot', false)
            ->assertSee('action="'.route('frontend.forms.submit', ['form' => 'public-contact']).'"', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_published_form_can_be_submitted_from_public_page(): void
    {
        Mail::fake();

        $form = $this->formWithFields([
            'name' => 'Lead form',
            'slug' => 'lead-form',
            'recipient_email' => 'admin@example.com',
            'success_message' => 'Thanks for reaching out.',
        ]);

        $this->from('/forms/lead-form')
            ->post('/forms/lead-form', [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'message' => 'Hello from the frontend.',
            ])
            ->assertRedirect('/forms/lead-form')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('form_submissions', [
            'form_id' => $form->id,
            'status' => 'new',
        ]);
        $this->assertDatabaseHas('form_submission_answers', [
            'field_name' => 'email',
            'value' => 'jane@example.com',
        ]);
        Mail::assertSent(FormSubmissionNotification::class);
    }

    public function test_localized_form_routes_use_the_active_locale_for_rendering_and_submission(): void
    {
        Mail::fake();
        $this->seed(CountryLanguageSeeder::class);
        $this->seed(TranslationModuleSeeder::class);

        CmsLanguage::query()->updateOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'slug' => 'english',
                'native_name' => 'English',
                'direction' => 'ltr',
                'status' => 'active',
                'is_enabled' => true,
                'is_default' => false,
            ],
        );

        $this->formWithFields([
            'name' => 'Nederlands formulier',
            'slug' => 'nederlands-formulier',
            'locale' => 'nl',
        ]);
        $englishForm = $this->formWithFields([
            'name' => 'English form',
            'slug' => 'english-form',
            'locale' => 'en',
            'recipient_email' => 'admin@example.com',
        ]);

        $this->get('/en/forms')
            ->assertOk()
            ->assertSee('English form')
            ->assertDontSee('Nederlands formulier');

        $this->get('/en/forms/english-form')
            ->assertOk()
            ->assertSee('English form')
            ->assertSee('action="'.route('frontend.locale.forms.submit', ['locale' => 'en', 'form' => 'english-form']).'"', false);

        $this->post('/en/forms/english-form', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello in English.',
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('form_submissions', [
            'form_id' => $englishForm->id,
            'locale' => 'en',
        ]);
        $this->get('/en/forms/nederlands-formulier')->assertNotFound();
    }

    public function test_draft_forms_are_not_publicly_available_or_submittable(): void
    {
        $this->formWithFields([
            'name' => 'Draft form',
            'slug' => 'draft-form',
            'status' => 'draft',
        ]);

        $this->get('/forms')
            ->assertOk()
            ->assertDontSee('Draft form');

        $this->get('/forms/draft-form')->assertNotFound();
        $this->post('/forms/draft-form')->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function formWithFields(array $attributes = []): Form
    {
        $form = Form::query()->create([
            'name' => $attributes['name'] ?? 'Test form',
            'slug' => $attributes['slug'] ?? 'test-form',
            'locale' => $attributes['locale'] ?? 'nl',
            'description' => $attributes['description'] ?? 'A public form.',
            'submit_text' => $attributes['submit_text'] ?? 'Send',
            'success_message' => $attributes['success_message'] ?? 'Thanks.',
            'recipient_email' => $attributes['recipient_email'] ?? null,
            'status' => $attributes['status'] ?? 'published',
            'sort_order' => $attributes['sort_order'] ?? 1,
            'settings' => [
                'show_title' => $attributes['show_title'] ?? true,
                'store_submissions' => true,
            ],
        ]);

        $block = $form->blocks()->create([
            'title' => 'Contact details',
            'sort_order' => 1,
        ]);
        $row = $block->rows()->create(['sort_order' => 1]);
        $row->fields()->create([
            'name' => 'name',
            'label' => 'Your name',
            'type' => 'input',
            'is_required' => true,
            'sort_order' => 1,
            'settings' => ['placeholder' => 'Your name'],
        ]);
        $row->fields()->create([
            'name' => 'email',
            'label' => 'E-mail',
            'type' => 'email',
            'is_required' => true,
            'sort_order' => 2,
            'settings' => ['placeholder' => 'E-mail'],
        ]);
        $row->fields()->create([
            'name' => 'message',
            'label' => 'Message',
            'type' => 'textarea',
            'is_required' => true,
            'sort_order' => 3,
            'settings' => ['placeholder' => 'Message'],
        ]);

        return $form;
    }
}
