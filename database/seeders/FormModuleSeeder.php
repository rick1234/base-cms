<?php

namespace Database\Seeders;

use App\Models\Cms\Form;
use App\Models\Cms\FormCategory;
use Illuminate\Database\Seeder;

class FormModuleSeeder extends Seeder
{
    public function run(): void
    {
        $category = FormCategory::query()->firstOrCreate(
            ['slug' => 'contact'],
            [
                'name' => 'Contact',
                'description' => 'Reusable contact and lead forms.',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );

        foreach ($this->forms() as $index => $data) {
            $form = Form::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'locale' => $data['locale'],
                    'description' => $data['description'],
                    'submit_text' => $data['submit_text'],
                    'success_message' => $data['success_message'],
                    'recipient_email' => 'admin@example.com',
                    'status' => 'published',
                    'sort_order' => $index + 1,
                    'settings' => [
                        'show_title' => true,
                        'layout' => 'default',
                        'mail_template' => 'mail.forms.submission',
                        'store_submissions' => true,
                        'confirmation_email_field' => 'email',
                    ],
                ],
            );

            $form->categories()->syncWithoutDetaching([$category->id => ['sort_order' => $index + 1]]);
            $this->ensureRecipients($form);
            $this->ensureMessages($form, $data);
            $this->ensureStructure($form, $data);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function forms(): array
    {
        return [
            [
                'name' => 'Voorbeeld contactformulier',
                'slug' => 'seeded-contact-form',
                'locale' => 'nl',
                'description' => 'Een voorbeeld voor de formulierbouwer.',
                'submit_text' => 'Versturen',
                'success_message' => 'Bedankt. We hebben je bericht ontvangen.',
                'block_title' => 'Contactgegevens',
                'name_label' => 'Naam',
                'email_label' => 'E-mail',
                'subject_label' => 'Onderwerp',
                'message_label' => 'Bericht',
                'message_placeholder' => 'Bericht',
                'options' => ['question' => 'Vraag', 'quote' => 'Offerte', 'support' => 'Support'],
                'notification_name' => 'Admin melding',
                'notification_subject' => 'Nieuwe contactaanvraag: {name}',
                'notification_body' => "Een bezoeker heeft het contactformulier ingevuld.\n\n{{summary}}",
                'confirmation_name' => 'Bevestiging bezoeker',
                'confirmation_subject' => 'We hebben je bericht ontvangen',
                'confirmation_body' => "Hallo {name},\n\nBedankt voor je bericht. We reageren waar nodig.",
            ],
            [
                'name' => 'Seeded contact form',
                'slug' => 'seeded-contact-form-en',
                'locale' => 'en',
                'description' => 'A seeded form builder example.',
                'submit_text' => 'Send',
                'success_message' => 'Thanks. We have received your message.',
                'block_title' => 'Contact details',
                'name_label' => 'Name',
                'email_label' => 'E-mail',
                'subject_label' => 'Subject',
                'message_label' => 'Message',
                'message_placeholder' => 'Message',
                'options' => ['question' => 'Question', 'quote' => 'Quote', 'support' => 'Support'],
                'notification_name' => 'Admin notification',
                'notification_subject' => 'New contact request: {name}',
                'notification_body' => "A visitor submitted the contact form.\n\n{{summary}}",
                'confirmation_name' => 'Submitter confirmation',
                'confirmation_subject' => 'We received your message',
                'confirmation_body' => "Hi {name},\n\nThank you for your message. We will respond where needed.",
            ],
        ];
    }

    private function ensureRecipients(Form $form): void
    {
        $form->recipients()->updateOrCreate(
            ['email' => 'admin@example.com', 'type' => 'to'],
            ['name' => 'Website admin', 'is_active' => true, 'sort_order' => 1],
        );

        $form->recipients()->updateOrCreate(
            ['email' => 'sales@example.com', 'type' => 'cc'],
            ['name' => 'Sales', 'is_active' => true, 'sort_order' => 2],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureMessages(Form $form, array $data): void
    {
        $form->messages()->updateOrCreate(
            ['type' => 'notification'],
            [
                'name' => $data['notification_name'],
                'subject' => $data['notification_subject'],
                'body' => $data['notification_body'],
                'is_active' => true,
                'sort_order' => 1,
                'settings' => ['layout' => 'default'],
            ],
        );

        $form->messages()->updateOrCreate(
            ['type' => 'confirmation'],
            [
                'name' => $data['confirmation_name'],
                'subject' => $data['confirmation_subject'],
                'body' => $data['confirmation_body'],
                'is_active' => true,
                'sort_order' => 2,
                'settings' => ['layout' => 'default'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureStructure(Form $form, array $data): void
    {
        if ($form->blocks()->exists()) {
            return;
        }

        $block = $form->blocks()->create([
            'title' => $data['block_title'],
            'sort_order' => 1,
        ]);

        $row = $block->rows()->create(['sort_order' => 1]);

        $row->fields()->create([
            'name' => 'name',
            'label' => $data['name_label'],
            'type' => 'input',
            'is_required' => true,
            'sort_order' => 1,
            'settings' => ['placeholder' => $data['name_label'], 'label_visible' => true, 'width' => 100],
        ]);

        $row->fields()->create([
            'name' => 'email',
            'label' => $data['email_label'],
            'type' => 'email',
            'is_required' => true,
            'sort_order' => 2,
            'settings' => ['placeholder' => $data['email_label'], 'label_visible' => true, 'width' => 100],
        ]);

        $subjectField = $row->fields()->create([
            'name' => 'subject',
            'label' => $data['subject_label'],
            'type' => 'select',
            'is_required' => true,
            'sort_order' => 3,
            'settings' => ['label_visible' => true, 'width' => 100],
        ]);

        foreach ($data['options'] as $value => $label) {
            $subjectField->options()->create([
                'label' => $label,
                'value' => $value,
                'sort_order' => $subjectField->options()->count() + 1,
            ]);
        }

        $row->fields()->create([
            'name' => 'message',
            'label' => $data['message_label'],
            'type' => 'textarea',
            'is_required' => true,
            'sort_order' => 4,
            'settings' => ['placeholder' => $data['message_placeholder'], 'label_visible' => true, 'width' => 100],
        ]);
    }
}
