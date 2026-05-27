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

        $form = Form::query()->firstOrCreate(
            ['slug' => 'seeded-contact-form'],
            [
                'name' => 'Seeded contact form',
                'locale' => 'nl',
                'description' => 'A seeded form builder example.',
                'submit_text' => 'Versturen',
                'success_message' => 'Bedankt. We hebben je bericht ontvangen.',
                'recipient_email' => 'admin@example.com',
                'status' => 'published',
                'settings' => [
                    'show_title' => true,
                    'layout' => 'default',
                    'mail_template' => 'forms.default',
                    'store_submissions' => true,
                    'honeypot_enabled' => true,
                    'honeypot_field' => 'website',
                    'confirmation_email_field' => 'email',
                ],
            ],
        );

        $form->categories()->syncWithoutDetaching([$category->id => ['sort_order' => 1]]);

        $form->recipients()->firstOrCreate(
            ['email' => 'admin@example.com', 'type' => 'to'],
            ['name' => 'Website admin', 'is_active' => true, 'sort_order' => 1],
        );

        $form->recipients()->firstOrCreate(
            ['email' => 'sales@example.com', 'type' => 'cc'],
            ['name' => 'Sales', 'is_active' => true, 'sort_order' => 2],
        );

        $form->messages()->firstOrCreate(
            ['type' => 'notification'],
            [
                'name' => 'Admin notification',
                'subject' => 'New contact request: {name}',
                'body' => "A visitor submitted the contact form.\n\n{{summary}}",
                'is_active' => true,
                'sort_order' => 1,
                'settings' => ['layout' => 'default'],
            ],
        );

        $form->messages()->firstOrCreate(
            ['type' => 'confirmation'],
            [
                'name' => 'Submitter confirmation',
                'subject' => 'We received your message',
                'body' => "Hi {name},\n\nThank you for your message. We will respond where needed.",
                'is_active' => true,
                'sort_order' => 2,
                'settings' => ['layout' => 'default'],
            ],
        );

        if ($form->blocks()->exists()) {
            return;
        }

        $block = $form->blocks()->create([
            'title' => 'Contactgegevens',
            'sort_order' => 1,
        ]);

        $row = $block->rows()->create(['sort_order' => 1]);

        $nameField = $row->fields()->create([
            'name' => 'name',
            'label' => 'Naam',
            'type' => 'input',
            'is_required' => true,
            'sort_order' => 1,
            'settings' => ['placeholder' => 'Naam', 'label_visible' => true, 'width' => 100],
        ]);

        $emailField = $row->fields()->create([
            'name' => 'email',
            'label' => 'E-mail',
            'type' => 'email',
            'is_required' => true,
            'sort_order' => 2,
            'settings' => ['placeholder' => 'E-mail', 'label_visible' => true, 'width' => 100],
        ]);

        $subjectField = $row->fields()->create([
            'name' => 'subject',
            'label' => 'Onderwerp',
            'type' => 'select',
            'is_required' => true,
            'sort_order' => 3,
            'settings' => ['label_visible' => true, 'width' => 100],
        ]);

        foreach (['question' => 'Vraag', 'quote' => 'Offerte', 'support' => 'Support'] as $value => $label) {
            $subjectField->options()->create([
                'label' => $label,
                'value' => $value,
                'sort_order' => $subjectField->options()->count() + 1,
            ]);
        }

        $row->fields()->create([
            'name' => 'message',
            'label' => 'Bericht',
            'type' => 'textarea',
            'is_required' => true,
            'sort_order' => 4,
            'settings' => ['placeholder' => 'Bericht', 'label_visible' => true, 'width' => 100],
        ]);

        $nameField->touch();
        $emailField->touch();
    }
}
