<?php

namespace Database\Seeders;

use App\Models\Cms\CatalogBrand;
use App\Models\Cms\CatalogCategory;
use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogProductImage;
use App\Models\Cms\CatalogProductOption;
use App\Models\Cms\CatalogProductTranslation;
use App\Models\Cms\CatalogPromotion;
use App\Models\Cms\CatalogReview;
use App\Models\Cms\CatalogStock;
use App\Models\Cms\CmsRedirect;
use App\Models\Cms\ContentCategory;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Domain;
use App\Models\Cms\Download;
use App\Models\Cms\DownloadCategory;
use App\Models\Cms\Event;
use App\Models\Cms\EventCategory;
use App\Models\Cms\FaqCategory;
use App\Models\Cms\FaqItem;
use App\Models\Cms\Form;
use App\Models\Cms\FormCategory;
use App\Models\Cms\Location;
use App\Models\Cms\LocationCategory;
use App\Models\Cms\LocationOpeningHour;
use App\Models\Cms\NavigationMenu;
use App\Models\Cms\NavigationMenuItem;
use App\Models\Cms\Page;
use App\Models\User;
use Database\Seeders\Support\SeederFiles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoWebsiteSeeder extends Seeder
{
    private ?int $adminId = null;

    public function run(): void
    {
        $this->adminId = User::query()->where('email', 'admin@example.com')->value('id');

        $forms = $this->seedForms();
        $domain = $this->configureDemoDomain($forms['contact']);
        $pages = $this->seedPages($domain);
        $contentCategories = $this->seedContent($forms);
        $catalogCategories = $this->seedCatalog();
        $events = $this->seedEvents($forms);
        $downloads = $this->seedDownloads();
        $this->seedLocations();
        $this->seedFaq();
        $this->seedRedirects($events);
        $this->seedNavigation($pages, $contentCategories, $catalogCategories, $events, $downloads);
    }

    /**
     * @return array{contact: Form, contact_en: Form, quote: Form, quote_en: Form, event: Form, event_en: Form}
     */
    private function seedForms(): array
    {
        $contactCategory = FormCategory::query()->updateOrCreate(
            ['slug' => 'contact'],
            [
                'name' => 'Contact',
                'description' => 'Forms for contact, quotes, and follow-up requests.',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );

        $forms = [];

        foreach ($this->demoForms() as $index => $data) {
            $form = Form::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'locale' => $data['locale'],
                    'description' => $data['description'],
                    'submit_text' => $data['submit_text'],
                    'success_message' => $data['success_message'],
                    'recipient_email' => $data['recipient_email'],
                    'status' => 'published',
                    'sort_order' => $index + 1,
                    'settings' => $data['settings'],
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );

            $form->categories()->syncWithoutDetaching([
                $contactCategory->id => ['sort_order' => $index + 1],
            ]);

            $this->ensureFormStructure($form, $data);
            $forms[$data['key']] = $form;
        }

        return $forms;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function demoForms(): array
    {
        return [
            [
                'key' => 'contact',
                'slug' => 'seeded-contact-form',
                'locale' => 'nl',
                'name' => 'Contactformulier',
                'description' => 'Algemeen contactformulier voor de demo website.',
                'submit_text' => 'Bericht versturen',
                'success_message' => 'Bedankt. We nemen snel contact met je op.',
                'recipient_email' => 'admin@example.com',
                'settings' => [
                    'show_title' => true,
                    'layout' => 'default',
                    'store_submissions' => true,
                    'honeypot_enabled' => true,
                    'honeypot_field' => 'website',
                    'confirmation_email_field' => 'email',
                ],
                'block_title' => 'Gegevens',
                'fields' => [
                    'name' => ['label' => 'Naam', 'placeholder' => 'Naam'],
                    'email' => ['label' => 'E-mail', 'placeholder' => 'E-mail'],
                    'subject' => ['label' => 'Onderwerp', 'options' => ['project' => 'Project', 'support' => 'Support', 'event' => 'Evenement']],
                    'message' => ['label' => 'Bericht', 'placeholder' => 'Vertel ons wat je nodig hebt'],
                ],
                'notification_name' => 'Contactmelding',
                'notification_subject' => 'Nieuwe inzending via contactformulier: {name}',
                'notification_body' => "Een bezoeker heeft het contactformulier ingevuld.\n\n{{summary}}",
            ],
            [
                'key' => 'contact_en',
                'slug' => 'seeded-contact-form-en',
                'locale' => 'en',
                'name' => 'Contact form',
                'description' => 'General contact form for the demo website.',
                'submit_text' => 'Send message',
                'success_message' => 'Thanks. We will get back to you soon.',
                'recipient_email' => 'admin@example.com',
                'settings' => [
                    'show_title' => true,
                    'layout' => 'default',
                    'store_submissions' => true,
                    'honeypot_enabled' => true,
                    'honeypot_field' => 'website',
                    'confirmation_email_field' => 'email',
                ],
                'block_title' => 'Details',
                'fields' => [
                    'name' => ['label' => 'Name', 'placeholder' => 'Name'],
                    'email' => ['label' => 'Email', 'placeholder' => 'Email'],
                    'subject' => ['label' => 'Subject', 'options' => ['project' => 'Project', 'support' => 'Support', 'event' => 'Event']],
                    'message' => ['label' => 'Message', 'placeholder' => 'Tell us what you need'],
                ],
                'notification_name' => 'Contact notification',
                'notification_subject' => 'New contact form submission: {name}',
                'notification_body' => "A visitor submitted the contact form.\n\n{{summary}}",
            ],
            [
                'key' => 'quote',
                'slug' => 'quote-request',
                'locale' => 'nl',
                'name' => 'Offerteaanvraag',
                'description' => 'Leadformulier voor nieuwe websites en koppelingen.',
                'submit_text' => 'Offerte aanvragen',
                'success_message' => 'Bedankt. We bekijken je aanvraag en sturen de vervolgstappen.',
                'recipient_email' => 'sales@example.com',
                'settings' => [
                    'show_title' => true,
                    'layout' => 'default',
                    'store_submissions' => true,
                    'honeypot_enabled' => true,
                    'honeypot_field' => 'company_url',
                    'confirmation_email_field' => 'email',
                ],
                'block_title' => 'Aanvraag',
                'fields' => [
                    'name' => ['label' => 'Naam', 'placeholder' => 'Naam'],
                    'email' => ['label' => 'E-mail', 'placeholder' => 'E-mail'],
                    'subject' => ['label' => 'Type aanvraag', 'options' => ['project' => 'Project', 'support' => 'Support', 'event' => 'Evenement']],
                    'message' => ['label' => 'Toelichting', 'placeholder' => 'Beschrijf je vraag'],
                ],
                'notification_name' => 'Offertemelding',
                'notification_subject' => 'Nieuwe offerteaanvraag: {name}',
                'notification_body' => "Een bezoeker heeft een offerte aangevraagd.\n\n{{summary}}",
            ],
            [
                'key' => 'quote_en',
                'slug' => 'quote-request-en',
                'locale' => 'en',
                'name' => 'Quote request',
                'description' => 'Lead form for new website and integration projects.',
                'submit_text' => 'Request quote',
                'success_message' => 'Thanks. We will review your request and respond with next steps.',
                'recipient_email' => 'sales@example.com',
                'settings' => [
                    'show_title' => true,
                    'layout' => 'default',
                    'store_submissions' => true,
                    'honeypot_enabled' => true,
                    'honeypot_field' => 'company_url',
                    'confirmation_email_field' => 'email',
                ],
                'block_title' => 'Request',
                'fields' => [
                    'name' => ['label' => 'Name', 'placeholder' => 'Name'],
                    'email' => ['label' => 'Email', 'placeholder' => 'Email'],
                    'subject' => ['label' => 'Request type', 'options' => ['project' => 'Project', 'support' => 'Support', 'event' => 'Event']],
                    'message' => ['label' => 'Message', 'placeholder' => 'Describe your request'],
                ],
                'notification_name' => 'Quote notification',
                'notification_subject' => 'New quote request: {name}',
                'notification_body' => "A visitor requested a quote.\n\n{{summary}}",
            ],
            [
                'key' => 'event',
                'slug' => 'event-registration',
                'locale' => 'nl',
                'name' => 'Evenement aanmelden',
                'description' => 'Aanmeldformulier voor demo evenementen en workshops.',
                'submit_text' => 'Aanmelden',
                'success_message' => 'Bedankt, je aanmelding is ontvangen.',
                'recipient_email' => 'events@example.com',
                'settings' => [
                    'show_title' => true,
                    'layout' => 'default',
                    'store_submissions' => true,
                    'honeypot_enabled' => true,
                    'honeypot_field' => 'website',
                ],
                'block_title' => 'Aanmelding',
                'fields' => [
                    'name' => ['label' => 'Naam', 'placeholder' => 'Naam'],
                    'email' => ['label' => 'E-mail', 'placeholder' => 'E-mail'],
                    'subject' => ['label' => 'Onderdeel', 'options' => ['project' => 'Project', 'support' => 'Support', 'event' => 'Evenement']],
                    'message' => ['label' => 'Opmerking', 'placeholder' => 'Laat ons weten waarmee we rekening kunnen houden'],
                ],
                'notification_name' => 'Evenementmelding',
                'notification_subject' => 'Nieuwe evenementaanmelding: {name}',
                'notification_body' => "Een bezoeker heeft zich aangemeld voor een evenement.\n\n{{summary}}",
            ],
            [
                'key' => 'event_en',
                'slug' => 'event-registration-en',
                'locale' => 'en',
                'name' => 'Event registration',
                'description' => 'Registration form for demo events and workshops.',
                'submit_text' => 'Register',
                'success_message' => 'Thanks, your registration has been received.',
                'recipient_email' => 'events@example.com',
                'settings' => [
                    'show_title' => true,
                    'layout' => 'default',
                    'store_submissions' => true,
                    'honeypot_enabled' => true,
                    'honeypot_field' => 'website',
                ],
                'block_title' => 'Registration',
                'fields' => [
                    'name' => ['label' => 'Name', 'placeholder' => 'Name'],
                    'email' => ['label' => 'Email', 'placeholder' => 'Email'],
                    'subject' => ['label' => 'Session', 'options' => ['project' => 'Project', 'support' => 'Support', 'event' => 'Event']],
                    'message' => ['label' => 'Note', 'placeholder' => 'Let us know what we should take into account'],
                ],
                'notification_name' => 'Event notification',
                'notification_subject' => 'New event registration: {name}',
                'notification_body' => "A visitor registered for an event.\n\n{{summary}}",
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureFormStructure(Form $form, array $data): void
    {
        $form->recipients()->updateOrCreate(
            ['email' => $form->recipient_email ?: 'admin@example.com', 'type' => 'to'],
            [
                'name' => $form->name.' recipient',
                'is_active' => true,
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $form->messages()->updateOrCreate(
            ['type' => 'notification'],
            [
                'name' => $data['notification_name'],
                'subject' => $data['notification_subject'],
                'body' => $data['notification_body'],
                'is_active' => true,
                'sort_order' => 1,
                'settings' => ['layout' => 'default'],
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        if ($form->blocks()->exists()) {
            return;
        }

        $block = $form->blocks()->create([
            'title' => $data['block_title'],
            'sort_order' => 1,
            'created_by' => $this->adminId,
            'updated_by' => $this->adminId,
        ]);
        $row = $block->rows()->create([
            'sort_order' => 1,
            'created_by' => $this->adminId,
            'updated_by' => $this->adminId,
        ]);

        $row->fields()->create([
            'name' => 'name',
            'label' => $data['fields']['name']['label'],
            'type' => 'input',
            'is_required' => true,
            'sort_order' => 1,
            'settings' => ['placeholder' => $data['fields']['name']['placeholder'], 'label_visible' => true, 'width' => 100],
            'created_by' => $this->adminId,
            'updated_by' => $this->adminId,
        ]);

        $row->fields()->create([
            'name' => 'email',
            'label' => $data['fields']['email']['label'],
            'type' => 'email',
            'is_required' => true,
            'sort_order' => 2,
            'settings' => ['placeholder' => $data['fields']['email']['placeholder'], 'label_visible' => true, 'width' => 100],
            'created_by' => $this->adminId,
            'updated_by' => $this->adminId,
        ]);

        $subject = $row->fields()->create([
            'name' => 'subject',
            'label' => $data['fields']['subject']['label'],
            'type' => 'select',
            'is_required' => true,
            'sort_order' => 3,
            'settings' => ['label_visible' => true, 'width' => 100],
            'created_by' => $this->adminId,
            'updated_by' => $this->adminId,
        ]);

        foreach ($data['fields']['subject']['options'] as $value => $label) {
            $subject->options()->create([
                'label' => $label,
                'value' => $value,
                'sort_order' => $subject->options()->count() + 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ]);
        }

        $row->fields()->create([
            'name' => 'message',
            'label' => $data['fields']['message']['label'],
            'type' => 'textarea',
            'is_required' => true,
            'sort_order' => 4,
            'settings' => ['placeholder' => $data['fields']['message']['placeholder'], 'label_visible' => true, 'width' => 100],
            'created_by' => $this->adminId,
            'updated_by' => $this->adminId,
        ]);
    }

    private function configureDemoDomain(Form $contactForm): ?Domain
    {
        $domain = Domain::query()
            ->where('is_development', true)
            ->ordered()
            ->first()
            ?: Domain::query()->ordered()->first();

        if (! $domain instanceof Domain) {
            return null;
        }

        $domain->forceFill([
            'name' => 'Acme Digital Works',
            'company_name' => 'Acme Digital Works',
            'default_meta_title' => 'Acme Digital Works',
            'default_meta_description' => 'A demo website for a practical digital agency with services, products, events, downloads, locations, and support content.',
            'default_og_title' => 'Acme Digital Works',
            'default_og_description' => 'Explore a complete seeded Laravel CMS website with realistic module content.',
            'contact_form_id' => $contactForm->id,
            'social_links' => [
                ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/example'],
                ['platform' => 'github', 'label' => 'GitHub', 'url' => 'https://github.com/example'],
            ],
        ])->save();

        return $domain;
    }

    /**
     * @return array<string, Page>
     */
    private function seedPages(?Domain $domain): array
    {
        $pages = [
            'home' => [
                'title' => 'Acme Digital Works',
                'navigation_label' => 'Home',
                'excerpt' => 'Websites, integrations, and support content for teams that want a calmer CMS.',
                'body' => [
                    'Acme Digital Works is a practical demo company used to show the base CMS as a complete, average-sized website.',
                    'The seeded site includes service pages, catalog categories, news, events, downloads, locations, FAQ content, and contact forms so the frontend and admin have realistic data to work with.',
                ],
                'meta_description' => 'A seeded demo website for the Laravel base CMS with content across every core module.',
                'sort_order' => 0,
            ],
            'about' => [
                'title' => 'Over Acme',
                'navigation_label' => 'Over ons',
                'excerpt' => 'A compact agency profile for the seeded demo website.',
                'body' => [
                    'Acme helps organizations turn scattered website needs into maintainable Laravel builds.',
                    'This page is intentionally plain text so public rendering stays safe while future templates can decide how to present rich content.',
                ],
                'meta_description' => 'Learn about the seeded Acme Digital Works demo company.',
                'sort_order' => 1,
            ],
            'services' => [
                'title' => 'Diensten',
                'navigation_label' => 'Diensten',
                'excerpt' => 'Strategy, websites, integrations, and ongoing support.',
                'body' => [
                    'Service content is seeded through pages and content categories so the navigation builder can link directly to a category and expand its children.',
                    'Use this section to test content relationships, search, SEO metadata, and module-aware navigation.',
                ],
                'meta_description' => 'Demo services for website strategy, Laravel implementation, integrations, and support.',
                'sort_order' => 2,
            ],
            'websites' => [
                'title' => 'Websites',
                'navigation_label' => 'Websites',
                'excerpt' => 'Rendered Laravel websites with clean CMS management.',
                'body' => ['Demo website service page for buildouts, content modeling, and SEO-ready page structures.'],
                'meta_description' => 'Website services seeded for the demo CMS.',
                'sort_order' => 3,
            ],
            'integrations' => [
                'title' => 'Koppelingen',
                'navigation_label' => 'Koppelingen',
                'excerpt' => 'Practical links between the CMS and business tools.',
                'body' => ['Demo integration page for CRM, catalog, search, and content workflows.'],
                'meta_description' => 'Integration services seeded for the demo CMS.',
                'sort_order' => 4,
            ],
            'support' => [
                'title' => 'Support',
                'navigation_label' => 'Support',
                'excerpt' => 'Ongoing care after launch.',
                'body' => ['Demo support page for retainers, maintenance, audits, and improvements.'],
                'meta_description' => 'Support services seeded for the demo CMS.',
                'sort_order' => 5,
            ],
            'products' => [
                'title' => 'Producten',
                'navigation_label' => 'Producten',
                'excerpt' => 'Demo catalog entries grouped by useful parent and child categories.',
                'body' => ['The product section is backed by catalog categories and seeded products so category expansion can be tested from the navigation builder.'],
                'meta_description' => 'Seeded product catalog overview for the Laravel base CMS.',
                'sort_order' => 6,
            ],
            'software' => [
                'title' => 'Software',
                'navigation_label' => 'Software',
                'excerpt' => 'CMS starter packages and extensions.',
                'body' => ['Catalog child category landing page for software-oriented demo products.'],
                'meta_description' => 'Software catalog category page for the demo CMS.',
                'sort_order' => 7,
            ],
            'service-packages' => [
                'title' => 'Servicepakketten',
                'navigation_label' => 'Servicepakketten',
                'excerpt' => 'Implementation and care packages.',
                'body' => ['Catalog child category landing page for service package demo products.'],
                'meta_description' => 'Service package catalog category page for the demo CMS.',
                'sort_order' => 8,
            ],
            'training' => [
                'title' => 'Training',
                'navigation_label' => 'Training',
                'excerpt' => 'Training and onboarding products.',
                'body' => ['Catalog child category landing page for training demo products.'],
                'meta_description' => 'Training catalog category page for the demo CMS.',
                'sort_order' => 9,
            ],
            'news' => [
                'title' => 'Nieuws',
                'navigation_label' => 'Nieuws',
                'excerpt' => 'Company news, product notes, and practical articles.',
                'body' => ['The news page is connected to content categories and seeded content items for search and navigation testing.'],
                'meta_description' => 'Seeded news overview for the Laravel base CMS.',
                'sort_order' => 10,
            ],
            'company-news' => [
                'title' => 'Bedrijfsnieuws',
                'navigation_label' => 'Bedrijfsnieuws',
                'excerpt' => 'Updates from the demo company.',
                'body' => ['Company news category landing page for the seeded content module.'],
                'meta_description' => 'Company news seeded for the demo CMS.',
                'sort_order' => 11,
            ],
            'insights' => [
                'title' => 'Insights',
                'navigation_label' => 'Insights',
                'excerpt' => 'Practical notes from delivery work.',
                'body' => ['Insights category landing page for the seeded content module.'],
                'meta_description' => 'Insights seeded for the demo CMS.',
                'sort_order' => 12,
            ],
            'events' => [
                'title' => 'Evenementen',
                'navigation_label' => 'Evenementen',
                'excerpt' => 'Workshops, webinars, and office sessions.',
                'body' => ['Event content is seeded with future dates so the admin has a useful schedule to manage.'],
                'meta_description' => 'Seeded events and workshops for the Laravel base CMS.',
                'sort_order' => 13,
            ],
            'downloads' => [
                'title' => 'Downloads',
                'navigation_label' => 'Downloads',
                'excerpt' => 'Brochures, checklists, guides, and onboarding files.',
                'body' => ['Download records include local files so protected and public download behavior can be tested.'],
                'meta_description' => 'Seeded downloads for the Laravel base CMS.',
                'sort_order' => 14,
            ],
            'locations' => [
                'title' => 'Vestigingen',
                'navigation_label' => 'Vestigingen',
                'excerpt' => 'Demo offices and showrooms around the Netherlands.',
                'body' => ['Location records include addresses, contact details, coordinates, and opening hours.'],
                'meta_description' => 'Seeded locations for the Laravel base CMS.',
                'sort_order' => 15,
            ],
            'faq' => [
                'title' => 'FAQ',
                'navigation_label' => 'FAQ',
                'excerpt' => 'Answers to common project, CMS, and support questions.',
                'body' => ['FAQ content is seeded in multiple categories so the admin and search screens have realistic data.'],
                'meta_description' => 'Seeded FAQ content for the Laravel base CMS.',
                'sort_order' => 16,
            ],
            'contact' => [
                'title' => 'Contact',
                'navigation_label' => 'Contact',
                'excerpt' => 'Plan a project, ask a question, or request support.',
                'body' => ['Use the seeded contact and quote forms to test form rendering, submissions, recipients, and notifications.'],
                'meta_description' => 'Contact Acme Digital Works through the seeded Laravel CMS demo website.',
                'sort_order' => 17,
            ],
            'privacy' => [
                'title' => 'Privacyverklaring',
                'navigation_label' => 'Privacy',
                'excerpt' => 'A seeded privacy page for the footer navigation.',
                'body' => ['This seeded privacy page gives the footer navigation a realistic legal link without adding custom website-specific logic.'],
                'meta_description' => 'Seeded privacy statement for the Laravel base CMS demo website.',
                'sort_order' => 18,
            ],
            'terms' => [
                'title' => 'Algemene voorwaarden',
                'navigation_label' => 'Voorwaarden',
                'excerpt' => 'A seeded terms page for the footer navigation.',
                'body' => ['This seeded terms page is a placeholder for project terms, service conditions, and delivery agreements.'],
                'meta_description' => 'Seeded terms and conditions page for the Laravel base CMS demo website.',
                'sort_order' => 19,
            ],
        ];

        $models = [];

        foreach ($pages as $slug => $page) {
            $models[$slug] = $this->page(null, $slug, $page);
        }

        if ($domain instanceof Domain) {
            $this->page($domain->id, 'home', $pages['home']);
        }

        return $models;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function page(?int $domainId, string $slug, array $attributes): Page
    {
        $page = Page::query()->firstOrNew([
            'domain_id' => $domainId,
            'slug' => $slug,
        ]);

        if (! $page->exists && ! $page->uuid) {
            $page->uuid = (string) Str::uuid();
        }

        $page->fill([
            'title' => $attributes['title'],
            'navigation_label' => $attributes['navigation_label'],
            'excerpt' => $attributes['excerpt'],
            'body' => implode("\n\n", $attributes['body']),
            'meta_title' => $attributes['title'],
            'meta_description' => $attributes['meta_description'],
            'og_title' => $attributes['title'],
            'og_description' => $attributes['meta_description'],
            'template' => 'default',
            'status' => 'published',
            'sort_order' => $attributes['sort_order'],
            'published_at' => now(),
            'created_by' => $this->adminId,
            'updated_by' => $this->adminId,
        ])->save();

        return $page;
    }

    /**
     * @param  array{contact: Form, contact_en: Form}  $forms
     * @return array<string, ContentCategory>
     */
    private function seedContent(array $forms): array
    {
        $services = $this->contentCategory('services', 'Diensten', 'Service categories for the demo website.', '/services', null, 1);
        $websites = $this->contentCategory('websites', 'Websites', 'Website strategy and implementation.', '/websites', $services, 1);
        $integrations = $this->contentCategory('integrations', 'Koppelingen', 'System and workflow integrations.', '/integrations', $services, 2);
        $support = $this->contentCategory('support', 'Support', 'Care, maintenance, and improvements.', '/support', $services, 3);

        $news = $this->contentCategory('news', 'Nieuws', 'News and updates from the demo company.', '/news', null, 2);
        $companyNews = $this->contentCategory('company-news', 'Bedrijfsnieuws', 'Company announcements and releases.', '/company-news', $news, 1);
        $insights = $this->contentCategory('insights', 'Insights', 'Practical articles and implementation notes.', '/insights', $news, 2);

        $items = [
            [
                'slug' => 'website-redesign-checklist',
                'title' => 'Website redesign checklist',
                'subtitle' => 'A practical planning guide',
                'meta_description' => 'Planning guide for CMS rebuilds, migrations, redirects, SEO metadata, accessibility, and ownership.',
                'categories' => [$insights, $websites],
                'sort_order' => 1,
            ],
            [
                'slug' => 'launching-the-acme-demo-site',
                'title' => 'Launching the Acme demo site',
                'subtitle' => 'Seeded company news',
                'meta_description' => 'Demo news item for seeded CMS module content, category relationships, and public metadata.',
                'categories' => [$news, $companyNews],
                'sort_order' => 2,
            ],
            [
                'slug' => 'how-we-model-catalog-content',
                'title' => 'How we model catalog content',
                'subtitle' => 'Products without the clutter',
                'meta_description' => 'Implementation note about clear catalog category trees, product metadata, and useful navigation links.',
                'categories' => [$insights],
                'sort_order' => 3,
            ],
            [
                'slug' => 'support-retainers-that-actually-help',
                'title' => 'Support retainers that actually help',
                'subtitle' => 'Care after launch',
                'meta_description' => 'Support article about care models, releases, small improvements, monitoring, and content help.',
                'categories' => [$support, $insights],
                'sort_order' => 4,
            ],
            [
                'slug' => 'new-integration-playbook',
                'title' => 'New integration playbook',
                'subtitle' => 'From scattered tools to stable flows',
                'meta_description' => 'Integration update for forms, catalogs, CRM workflows, search, and admin editing.',
                'categories' => [$integrations, $companyNews],
                'sort_order' => 5,
            ],
            [
                'slug' => 'editor-training-day-announced',
                'title' => 'Editor training day announced',
                'subtitle' => 'Hands-on CMS onboarding',
                'meta_description' => 'Announcement for a hands-on CMS onboarding day for editors.',
                'categories' => [$news, $companyNews],
                'sort_order' => 6,
            ],
        ];

        foreach ($items as $item) {
            foreach ($this->localizedContentItem($item) as $localized) {
                $contentItem = ContentItem::query()->updateOrCreate(
                    ['slug' => $localized['slug']],
                    [
                        'title' => $localized['title'],
                        'subtitle' => $localized['subtitle'],
                        'meta_title' => $localized['title'],
                        'meta_description' => $localized['meta_description'],
                        'locale' => $localized['locale'],
                        'form_id' => $item['slug'] === 'website-redesign-checklist'
                            ? $forms[$localized['locale'] === 'nl' ? 'contact' : 'contact_en']->id
                            : null,
                        'status' => 'published',
                        'active_from' => now()->subDays(14)->toDateString(),
                        'active_until' => now()->addYear()->toDateString(),
                        'sort_order' => $item['sort_order'],
                        'created_by' => $this->adminId,
                        'updated_by' => $this->adminId,
                    ],
                );

                $contentItem->categories()->sync($this->categoryPivot($item['categories']));
            }
        }

        return [
            'services' => $services,
            'websites' => $websites,
            'integrations' => $integrations,
            'support' => $support,
            'news' => $news,
            'company-news' => $companyNews,
            'insights' => $insights,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return list<array{slug: string, locale: string, title: string, subtitle: string, meta_description: string}>
     */
    private function localizedContentItem(array $item): array
    {
        $dutch = match ($item['slug']) {
            'website-redesign-checklist' => [
                'title' => 'Checklist website redesign',
                'subtitle' => 'Een praktische planningsgids',
                'meta_description' => 'Planningsgids voor CMS herbouw, migraties, redirects, SEO metadata, toegankelijkheid en eigenaarschap.',
            ],
            'launching-the-acme-demo-site' => [
                'title' => 'Lancering van de Acme demo website',
                'subtitle' => 'Voorbeeld bedrijfsnieuws',
                'meta_description' => 'Voorbeeldnieuws voor CMS modulecontent, categorierelaties en publieke metadata.',
            ],
            'how-we-model-catalog-content' => [
                'title' => 'Hoe we cataloguscontent modelleren',
                'subtitle' => 'Producten zonder ruis',
                'meta_description' => 'Implementatienotitie over heldere catalogusbomen, productmetadata en nuttige navigatielinks.',
            ],
            'support-retainers-that-actually-help' => [
                'title' => 'Supportafspraken die echt helpen',
                'subtitle' => 'Zorg na livegang',
                'meta_description' => 'Supportartikel over onderhoud, releases, kleine verbeteringen, monitoring en hulp bij content.',
            ],
            'new-integration-playbook' => [
                'title' => 'Nieuw integratiehandboek',
                'subtitle' => 'Van losse tools naar stabiele processen',
                'meta_description' => 'Integratie-update voor formulieren, catalogi, CRM workflows, zoeken en beheer.',
            ],
            'editor-training-day-announced' => [
                'title' => 'Redactietraining aangekondigd',
                'subtitle' => 'Praktische CMS onboarding',
                'meta_description' => 'Aankondiging voor een praktische CMS onboardingdag voor redacteuren.',
            ],
            default => [
                'title' => $item['title'],
                'subtitle' => $item['subtitle'],
                'meta_description' => $item['meta_description'],
            ],
        };

        return [
            [
                'slug' => $item['slug'],
                'locale' => 'nl',
                ...$dutch,
            ],
            [
                'slug' => $item['slug'].'-en',
                'locale' => 'en',
                'title' => $item['title'],
                'subtitle' => $item['subtitle'],
                'meta_description' => $item['meta_description'],
            ],
        ];
    }

    private function contentCategory(
        string $slug,
        string $name,
        string $description,
        string $customUrl,
        ?ContentCategory $parent,
        int $sortOrder,
    ): ContentCategory {
        return ContentCategory::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'parent_id' => $parent?->id,
                'name' => $name,
                'description' => $description,
                'custom_url' => $customUrl,
                'meta_description' => $description,
                'status' => 'active',
                'is_hidden_from_navigation' => false,
                'sort_order' => $sortOrder,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );
    }

    /**
     * @return array<string, CatalogCategory>
     */
    private function seedCatalog(): array
    {
        $products = $this->catalogCategory('products', 'Producten', 'Root product category for the demo catalog.', null, 1);
        $software = $this->catalogCategory('software', 'Software', 'CMS software and extension packages.', $products, 1);
        $packages = $this->catalogCategory('service-packages', 'Servicepakketten', 'Implementation and support packages.', $products, 2);
        $training = $this->catalogCategory('training', 'Training', 'Training and onboarding products.', $products, 3);

        $brand = CatalogBrand::query()->updateOrCreate(
            ['slug' => 'acme-digital'],
            [
                'name' => 'Acme Digital',
                'description' => 'Demo brand for seeded catalog products.',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $promotion = CatalogPromotion::query()->updateOrCreate(
            ['slug' => 'demo-launch-bundle'],
            [
                'name' => 'Demo launch bundle',
                'description' => 'Seeded promotion for product and package examples.',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $productsToSeed = [
            ['DEMO-CMS-STARTER', 'CMS starter package', 'A starter implementation package for small websites.', 349500, [$products, $software], true],
            ['DEMO-CMS-PRO', 'CMS professional package', 'A larger setup with content modeling, search, and launch support.', 749500, [$products, $software, $packages], true],
            ['DEMO-CARE-20', 'Care plan 20', 'Monthly improvement and maintenance plan for growing teams.', 199500, [$products, $packages], false],
            ['DEMO-SEO-AUDIT', 'SEO technical audit', 'A structured audit for redirects, metadata, performance, and content hierarchy.', 225000, [$products, $packages], false],
            ['DEMO-TRAINING-EDITOR', 'Editor training session', 'Hands-on CMS training for content editors.', 95000, [$products, $training], false],
            ['DEMO-INTEGRATION-KIT', 'Integration planning kit', 'A planning package for CRM, catalog, and form integrations.', 175000, [$products, $software], false],
        ];

        foreach ($productsToSeed as $index => [$sku, $name, $description, $price, $categories, $onSale]) {
            $product = CatalogProduct::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'price_note' => 'Excludes VAT.',
                    'is_on_sale' => $onSale,
                    'sale_price' => $onSale ? (int) round($price * 0.9) : null,
                    'sale_price_note' => $onSale ? 'Demo launch price.' : null,
                    'meta_title' => $name,
                    'meta_description' => $description,
                    'brand_id' => $brand->id,
                    'promotion_id' => $onSale ? $promotion->id : null,
                    'can_be_engraved' => false,
                    'status' => 'published',
                    'active_from' => now()->subDays(7)->toDateString(),
                    'active_until' => now()->addYear()->toDateString(),
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );

            $product->categories()->sync($this->categoryPivot($categories));
            $this->seedProductDetails($product, $index + 1);
        }

        return [
            'products' => $products,
            'software' => $software,
            'service-packages' => $packages,
            'training' => $training,
        ];
    }

    private function catalogCategory(string $slug, string $name, string $description, ?CatalogCategory $parent, int $sortOrder): CatalogCategory
    {
        return CatalogCategory::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'parent_id' => $parent?->id,
                'name' => $name,
                'description' => $description,
                'meta_title' => $name,
                'status' => 'active',
                'is_hidden_from_navigation' => false,
                'sort_order' => $sortOrder,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );
    }

    private function seedProductDetails(CatalogProduct $product, int $sortOrder): void
    {
        $imageFixture = $this->imageFixture($sortOrder);
        $imagePath = SeederFiles::publicImage(
            $imageFixture,
            'admin/uploads/catalog/images',
            'demo-product-'.$product->id.'.'.pathinfo($imageFixture, PATHINFO_EXTENSION),
        );

        CatalogProductImage::query()->updateOrCreate(
            ['catalog_product_id' => $product->id, 'image_path' => $imagePath],
            [
                'folder' => 'storage/admin/uploads/catalog/images',
                'caption' => $product->name,
                'sort_order' => $sortOrder,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        foreach ([
            'nl' => [
                'option_label' => 'Levering',
                'option_value' => 'Planningsgesprek en schriftelijke overdracht inbegrepen.',
                'subtitle' => 'Voorbeeldproduct voor de demo catalogus',
                'content' => $product->description,
            ],
            'en' => [
                'option_label' => 'Delivery',
                'option_value' => 'Planning call and written handover included.',
                'subtitle' => 'Seeded demo catalog product',
                'content' => $product->description,
            ],
        ] as $locale => $translation) {
            CatalogProductOption::query()->updateOrCreate(
                ['catalog_product_id' => $product->id, 'locale' => $locale, 'label' => $translation['option_label']],
                [
                    'value' => $translation['option_value'],
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );

            CatalogProductTranslation::query()->updateOrCreate(
                ['catalog_product_id' => $product->id, 'locale' => $locale],
                [
                    'title' => $product->name,
                    'subtitle' => $translation['subtitle'],
                    'content' => $translation['content'],
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );
        }

        CatalogStock::query()->updateOrCreate(
            ['catalog_product_id' => $product->id, 'location' => 'Demo warehouse'],
            [
                'quantity' => 10 + $sortOrder,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        CatalogReview::query()->updateOrCreate(
            ['catalog_product_id' => $product->id, 'author_email' => 'client'.$sortOrder.'@example.com'],
            [
                'author_name' => 'Demo client '.$sortOrder,
                'rating' => 4 + ($sortOrder % 2),
                'status' => 'published',
                'title' => 'Useful demo package',
                'content' => 'This review gives the catalog module realistic public and admin data.',
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );
    }

    /**
     * @param  array{event: Form, event_en: Form}  $forms
     * @return array<string, Event>
     */
    private function seedEvents(array $forms): array
    {
        $events = EventCategory::query()->updateOrCreate(
            ['slug' => 'events'],
            [
                'name' => 'Evenementen',
                'description' => 'Events, webinars, and workshops.',
                'status' => 'active',
                'is_hidden_from_navigation' => false,
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );
        $workshops = $this->eventCategory('workshops', 'Workshops', 'Hands-on training sessions.', $events, 1);
        $webinars = $this->eventCategory('webinars', 'Webinars', 'Online learning sessions.', $events, 2);
        $meetups = $this->eventCategory('meetups', 'Meetups', 'Office and community sessions.', $events, 3);

        $records = [
            ['cms-planning-workshop', 'CMS planning workshop', 'Plan content, navigation, and launch steps.', now()->addWeeks(3), [$events, $workshops]],
            ['seo-ready-content-webinar', 'SEO-ready content webinar', 'Prepare content for search and structured growth.', now()->addWeeks(5), [$events, $webinars]],
            ['editor-training-day', 'Editor training day', 'A practical day for content editors and admins.', now()->addMonths(2), [$events, $workshops]],
            ['open-office-demo-morning', 'Open office demo morning', 'Meet the team and walk through the demo website.', now()->addMonths(3), [$events, $meetups]],
        ];

        $models = [];

        foreach ($records as $index => [$slug, $title, $intro, $date, $categories]) {
            foreach ($this->localizedEvent($slug, $title, $intro) as $localized) {
                $event = Event::query()->updateOrCreate(
                    ['slug' => $localized['slug']],
                    [
                        'title' => $localized['title'],
                        'subtitle' => $localized['subtitle'],
                        'intro' => $localized['intro'],
                        'body' => $localized['body'],
                        'meta_title' => $localized['title'],
                        'meta_description' => $localized['intro'],
                        'locale' => $localized['locale'],
                        'form_id' => $forms[$localized['locale'] === 'nl' ? 'event' : 'event_en']->id,
                        'status' => 'published',
                        'active_from' => now()->subDay()->toDateString(),
                        'active_until' => now()->addYear()->toDateString(),
                        'starts_at' => $date->toDateString(),
                        'ends_at' => $date->copy()->addDay()->toDateString(),
                        'sort_order' => $index + 1,
                        'created_by' => $this->adminId,
                        'updated_by' => $this->adminId,
                    ],
                );

                $event->categories()->sync($this->categoryPivot($categories));
                $models[$localized['slug']] = $event;
            }
        }

        return $models;
    }

    /**
     * @return list<array{slug: string, locale: string, title: string, subtitle: string, intro: string, body: string}>
     */
    private function localizedEvent(string $slug, string $title, string $intro): array
    {
        $dutch = match ($slug) {
            'cms-planning-workshop' => [
                'title' => 'CMS planningsworkshop',
                'intro' => 'Plan content, navigatie en lancering in overzichtelijke stappen.',
            ],
            'seo-ready-content-webinar' => [
                'title' => 'SEO-ready content webinar',
                'intro' => 'Bereid content voor op zoekmachines en gestructureerde groei.',
            ],
            'editor-training-day' => [
                'title' => 'Redactietraining',
                'intro' => 'Een praktische dag voor redacteuren en beheerders.',
            ],
            'open-office-demo-morning' => [
                'title' => 'Open kantoor demo-ochtend',
                'intro' => 'Ontmoet het team en loop samen door de demo website.',
            ],
            default => ['title' => $title, 'intro' => $intro],
        };

        return [
            [
                'slug' => $slug,
                'locale' => 'nl',
                'title' => $dutch['title'],
                'subtitle' => 'Demo evenement',
                'intro' => $dutch['intro'],
                'body' => 'Dit evenement is toegevoegd zodat de evenementenmodule na een nieuwe installatie direct bruikbare data heeft.',
            ],
            [
                'slug' => $slug.'-en',
                'locale' => 'en',
                'title' => $title,
                'subtitle' => 'Seeded demo event',
                'intro' => $intro,
                'body' => 'This event is seeded to make the events module useful after a fresh install.',
            ],
        ];
    }

    private function eventCategory(string $slug, string $name, string $description, EventCategory $parent, int $sortOrder): EventCategory
    {
        return EventCategory::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'parent_id' => $parent->id,
                'name' => $name,
                'description' => $description,
                'status' => 'active',
                'is_hidden_from_navigation' => false,
                'sort_order' => $sortOrder,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );
    }

    /**
     * @return array<string, Download>
     */
    private function seedDownloads(): array
    {
        $root = DownloadCategory::query()->updateOrCreate(
            ['slug' => 'downloads'],
            [
                'name' => 'Downloads',
                'description' => 'Public and protected demo downloads.',
                'status' => 'active',
                'is_hidden_from_navigation' => false,
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );
        $guides = $this->downloadCategory('guides', 'Gidsen', 'Public guides and checklists.', $root, 1);
        $onboarding = $this->downloadCategory('onboarding', 'Onboarding', 'Files for new clients.', $root, 2);
        $protected = $this->downloadCategory('protected-downloads', 'Beveiligde downloads', 'Files that require a password.', $root, 3);

        $records = [
            ['demo-service-guide', 'Servicegids', 'A public overview of demo services.', 'demo-service-guide.txt', [$root, $guides], false],
            ['website-launch-checklist', 'Livegang checklist', 'A checklist for releases and SEO basics.', 'website-launch-checklist.txt', [$root, $guides], false],
            ['client-onboarding-pack', 'Onboardingpakket', 'A practical onboarding file for new projects.', 'client-onboarding-pack.txt', [$root, $onboarding], false],
            ['protected-project-brief', 'Beveiligde projectbrief', 'A password protected example download.', 'protected-project-brief.txt', [$root, $protected], true],
        ];

        $downloads = [];

        foreach ($records as $index => [$slug, $name, $description, $filename, $categories, $protectedFile]) {
            $file = SeederFiles::privateDocument($filename, 'admin/downloads/demo', $filename);

            $download = Download::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $description,
                    'type' => $file['extension'],
                    'url' => $file['path'],
                    'file_disk' => $file['disk'],
                    'file_path' => $file['path'],
                    'original_filename' => $file['filename'],
                    'mime_type' => $file['mime_type'],
                    'file_size' => $file['size'],
                    'status' => 'active',
                    'active_from' => now()->subDay()->toDateString(),
                    'active_until' => now()->addYear()->toDateString(),
                    'is_password_protected' => $protectedFile,
                    'password_hash' => $protectedFile ? bcrypt('download-password') : null,
                    'link_expires_after_minutes' => $protectedFile ? null : 60,
                    'sort_order' => $index + 1,
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );

            $download->categories()->sync($this->categoryPivot($categories));
            $downloads[$slug] = $download;
        }

        return $downloads;
    }

    private function imageFixture(int $position): string
    {
        $fixtures = [
            'seed-image-01.jpg',
            'seed-image-02.png',
            'seed-image-03.jpg',
            'seed-image-04.jpg',
            'seed-image-05.jpg',
            'seed-image-06.png',
            'seed-image-07.jpg',
            'seed-image-08.jpg',
        ];

        return $fixtures[($position - 1) % count($fixtures)];
    }

    private function downloadCategory(string $slug, string $name, string $description, DownloadCategory $parent, int $sortOrder): DownloadCategory
    {
        return DownloadCategory::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'parent_id' => $parent->id,
                'name' => $name,
                'description' => $description,
                'status' => 'active',
                'is_hidden_from_navigation' => false,
                'sort_order' => $sortOrder,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );
    }

    private function seedLocations(): void
    {
        $root = LocationCategory::query()->updateOrCreate(
            ['slug' => 'locations'],
            [
                'name' => 'Vestigingen',
                'description' => 'Demo offices and showrooms.',
                'status' => 'active',
                'is_hidden_from_navigation' => false,
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );
        $offices = $this->locationCategory('offices', 'Kantoren', 'Regional office locations.', $root, 1);
        $showrooms = $this->locationCategory('showrooms', 'Showrooms', 'Places for demos and workshops.', $root, 2);

        $locations = [
            ['Amsterdam office', 'Keizersgracht 120', '1015 CW', 'Amsterdam', '52.3731', '4.8922', [$root, $offices, $showrooms]],
            ['Utrecht studio', 'Oudegracht 210', '3511 NS', 'Utrecht', '52.0907', '5.1214', [$root, $offices]],
            ['Eindhoven lab', 'Torenallee 20', '5617 BC', 'Eindhoven', '51.4416', '5.4697', [$root, $showrooms]],
        ];

        foreach ($locations as $index => [$name, $street, $postalCode, $city, $latitude, $longitude, $categories]) {
            $location = Location::query()->updateOrCreate(
                ['name' => $name],
                [
                    'street_address' => $street,
                    'postal_code' => $postalCode,
                    'city' => $city,
                    'country_code' => 'NL',
                    'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
                    'phone' => '+31 30 000 00 '.str_pad((string) ($index + 10), 2, '0', STR_PAD_LEFT),
                    'website_url' => 'https://example.com/locations',
                    'chamber_of_commerce_number' => '1234567'.($index + 1),
                    'description' => $name.' is a seeded location for demos, workshops, and contact data.',
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'map_info' => 'Seeded map marker for '.$city.'.',
                    'status' => 'active',
                    'active_from' => now()->subDay()->toDateString(),
                    'active_until' => now()->addYear()->toDateString(),
                    'sort_order' => $index + 1,
                    'metadata' => null,
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );

            $location->categories()->sync($this->categoryPivot($categories));
            $this->seedOpeningHours($location);
        }
    }

    private function locationCategory(string $slug, string $name, string $description, LocationCategory $parent, int $sortOrder): LocationCategory
    {
        return LocationCategory::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'parent_id' => $parent->id,
                'name' => $name,
                'description' => $description,
                'status' => 'active',
                'is_hidden_from_navigation' => false,
                'sort_order' => $sortOrder,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );
    }

    private function seedOpeningHours(Location $location): void
    {
        foreach (array_keys(Location::dayNames()) as $day) {
            $weekend = in_array($day, ['5', '6'], true);

            LocationOpeningHour::query()->updateOrCreate(
                ['location_id' => $location->id, 'day' => $day],
                [
                    'opens_at' => $weekend ? null : '09:00',
                    'closes_at' => $weekend ? null : '17:30',
                    'is_closed' => $weekend,
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );
        }
    }

    private function seedFaq(): void
    {
        $root = FaqCategory::query()->updateOrCreate(
            ['slug' => 'faq'],
            [
                'name' => 'FAQ',
                'description' => 'Frequently asked questions.',
                'status' => 'active',
                'is_hidden_from_navigation' => false,
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );
        $projects = $this->faqCategory('projects', 'Projects', 'Questions about planning and delivery.', $root, 1);
        $cms = $this->faqCategory('cms-usage', 'CMS usage', 'Questions about content editing and modules.', $root, 2);
        $support = $this->faqCategory('support-faq', 'Support', 'Questions about maintenance and care.', $root, 3);

        $records = [
            ['how-long-does-a-demo-project-take', 'How long does a typical website project take?', 'Most small to mid-sized projects fit into a four to eight week delivery window.', [$root, $projects]],
            ['can-content-be-managed-per-domain', 'Can content be managed per domain?', 'Yes. Pages and public content can be scoped to domains while global fallbacks remain available.', [$root, $cms]],
            ['does-the-cms-support-headless-api-usage', 'Does the CMS support headless API usage?', 'The base includes versioned API routes and API resources for headless usage.', [$root, $cms]],
            ['can-navigation-link-to-module-items', 'Can navigation link to module items?', 'Yes. Navigation items can link to pages, categories, content items, downloads, events, forms, and custom URLs.', [$root, $cms]],
            ['what-happens-with-external-menu-links', 'What happens with external menu links?', 'External custom URLs are detected and opened in a new tab on the frontend.', [$root, $cms]],
            ['is-support-available-after-launch', 'Is support available after launch?', 'The seeded care plans represent maintenance, improvements, audits, and release support.', [$root, $support]],
            ['can-downloads-be-protected', 'Can downloads be protected?', 'Downloads can be public or password protected with temporary access links.', [$root, $support]],
            ['how-do-editor-training-sessions-work', 'How do editor training sessions work?', 'Training sessions use the event and form modules for registration and follow-up.', [$root, $projects]],
        ];

        foreach ($records as $index => [$slug, $question, $answer, $categories]) {
            foreach ($this->localizedFaq($slug, $question, $answer) as $localized) {
                $faq = FaqItem::query()->updateOrCreate(
                    ['slug' => $localized['slug']],
                    [
                        'question' => $localized['question'],
                        'intro' => $localized['answer'],
                        'body' => $localized['answer'],
                        'meta_title' => $localized['question'],
                        'meta_description' => $localized['answer'],
                        'locale' => $localized['locale'],
                        'status' => 'published',
                        'active_from' => now()->subDay()->toDateString(),
                        'active_until' => now()->addYear()->toDateString(),
                        'sort_order' => $index + 1,
                        'created_by' => $this->adminId,
                        'updated_by' => $this->adminId,
                    ],
                );

                $faq->categories()->sync($this->categoryPivot($categories));
            }
        }
    }

    /**
     * @return list<array{slug: string, locale: string, question: string, answer: string}>
     */
    private function localizedFaq(string $slug, string $question, string $answer): array
    {
        $dutch = match ($slug) {
            'how-long-does-a-demo-project-take' => [
                'question' => 'Hoe lang duurt een gemiddeld websiteproject?',
                'answer' => 'De meeste kleine tot middelgrote projecten passen in een planning van vier tot acht weken.',
            ],
            'can-content-be-managed-per-domain' => [
                'question' => 'Kan content per domein beheerd worden?',
                'answer' => 'Ja. Pagina\'s en publieke content kunnen aan domeinen gekoppeld worden, met globale fallbacks waar nodig.',
            ],
            'does-the-cms-support-headless-api-usage' => [
                'question' => 'Ondersteunt het CMS headless API gebruik?',
                'answer' => 'De basis bevat versiebeheer voor API routes en API resources voor headless gebruik.',
            ],
            'can-navigation-link-to-module-items' => [
                'question' => 'Kan navigatie naar module-items linken?',
                'answer' => 'Ja. Navigatie-items kunnen linken naar pagina\'s, categorieen, contentitems, downloads, evenementen, formulieren en aangepaste URL\'s.',
            ],
            'what-happens-with-external-menu-links' => [
                'question' => 'Wat gebeurt er met externe menulinks?',
                'answer' => 'Externe aangepaste URL\'s worden herkend en openen op de frontend in een nieuw tabblad.',
            ],
            'is-support-available-after-launch' => [
                'question' => 'Is er support beschikbaar na livegang?',
                'answer' => 'De voorbeeld care pakketten staan voor onderhoud, verbeteringen, audits en ondersteuning bij releases.',
            ],
            'can-downloads-be-protected' => [
                'question' => 'Kunnen downloads beveiligd worden?',
                'answer' => 'Downloads kunnen openbaar of met een wachtwoord beschermd zijn, inclusief tijdelijke toegangslinks.',
            ],
            'how-do-editor-training-sessions-work' => [
                'question' => 'Hoe werken redactietrainingen?',
                'answer' => 'Trainingen gebruiken de evenementen- en formuliermodules voor aanmelding en opvolging.',
            ],
            default => ['question' => $question, 'answer' => $answer],
        };

        return [
            [
                'slug' => $slug,
                'locale' => 'nl',
                'question' => $dutch['question'],
                'answer' => $dutch['answer'],
            ],
            [
                'slug' => $slug.'-en',
                'locale' => 'en',
                'question' => $question,
                'answer' => $answer,
            ],
        ];
    }

    private function faqCategory(string $slug, string $name, string $description, FaqCategory $parent, int $sortOrder): FaqCategory
    {
        return FaqCategory::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'parent_id' => $parent->id,
                'name' => $name,
                'description' => $description,
                'status' => 'active',
                'is_hidden_from_navigation' => false,
                'sort_order' => $sortOrder,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );
    }

    /**
     * @param  array<string, Event>  $events
     */
    private function seedRedirects(array $events): void
    {
        foreach ([
            'products/software' => '/software',
            'products/service-packages' => '/service-packages',
            'products/training' => '/training',
            'events/events/workshops' => '/events',
            'events/events/webinars' => '/events',
            'events/events/meetups' => '/events',
        ] as $source => $target) {
            CmsRedirect::query()->updateOrCreate(
                ['source_path' => $source],
                [
                    'target_url' => $target,
                    'description' => 'Demo redirect for seeded navigation category URL '.$source.'.',
                    'status_code' => 301,
                    'is_active' => true,
                    'preserve_query' => true,
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );
        }

        foreach ($events as $event) {
            CmsRedirect::query()->updateOrCreate(
                ['source_path' => 'events/'.$event->slug],
                [
                    'target_url' => '/events',
                    'description' => 'Demo redirect for event detail URL '.$event->slug.'.',
                    'status_code' => 302,
                    'is_active' => true,
                    'preserve_query' => true,
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );
        }
    }

    /**
     * @param  array<string, Page>  $pages
     * @param  array<string, ContentCategory>  $contentCategories
     * @param  array<string, CatalogCategory>  $catalogCategories
     * @param  array<string, Event>  $events
     * @param  array<string, Download>  $downloads
     */
    private function seedNavigation(
        array $pages,
        array $contentCategories,
        array $catalogCategories,
        array $events,
        array $downloads,
    ): void {
        $primaryMenu = $this->navigationMenu('primary', 'Hoofdnavigatie', 1);
        $footerMenu = $this->navigationMenu('footer', 'Voeternavigatie', 2);

        $this->clearNavigationItems($primaryMenu);
        $this->clearNavigationItems($footerMenu);

        $this->navigationItem($primaryMenu, 'Home', 'page', $pages['home']->id, null, false, false, true, 1);
        $this->navigationItem($primaryMenu, 'Diensten', 'content_category', $contentCategories['services']->id, null, false, true, true, 2);
        $this->navigationItem($primaryMenu, 'Producten', 'catalog_category', $catalogCategories['products']->id, null, false, true, true, 3);
        $this->navigationItem($primaryMenu, 'Nieuws', 'content_category', $contentCategories['news']->id, null, false, true, true, 4);

        $eventsItem = $this->navigationItem($primaryMenu, 'Evenementen', 'page', $pages['events']->id, null, false, false, true, 5);
        $this->navigationItem($primaryMenu, 'CMS planningsworkshop', 'event', $events['cms-planning-workshop']->id, null, false, false, true, 1, $eventsItem);
        $this->navigationItem($primaryMenu, 'Redactietraining', 'event', $events['editor-training-day']->id, null, false, false, true, 2, $eventsItem);

        $downloadsItem = $this->navigationItem($primaryMenu, 'Downloads', 'page', $pages['downloads']->id, null, false, false, true, 6);
        $this->navigationItem($primaryMenu, 'Servicegids', 'download', $downloads['demo-service-guide']->id, null, false, false, true, 1, $downloadsItem);
        $this->navigationItem($primaryMenu, 'Livegang checklist', 'download', $downloads['website-launch-checklist']->id, null, false, false, true, 2, $downloadsItem);

        $this->navigationItem($primaryMenu, 'Vestigingen', 'page', $pages['locations']->id, null, false, false, true, 7);
        $this->navigationItem($primaryMenu, 'FAQ', 'page', $pages['faq']->id, null, false, false, true, 8);
        $this->navigationItem($primaryMenu, 'Contact', 'page', $pages['contact']->id, null, false, false, true, 9);
        $this->navigationItem($primaryMenu, 'Klantportaal', 'custom', null, 'https://portal.example.com', true, false, true, 10);
        $this->navigationItem($primaryMenu, 'Conceptcampagne', 'custom', null, '/draft-campaign', false, false, false, 99);

        $this->navigationItem($footerMenu, 'Over ons', 'page', $pages['about']->id, null, false, false, true, 1);
        $this->navigationItem($footerMenu, 'Downloads', 'page', $pages['downloads']->id, null, false, false, true, 2);
        $this->navigationItem($footerMenu, 'FAQ', 'page', $pages['faq']->id, null, false, false, true, 3);
        $this->navigationItem($footerMenu, 'Privacy', 'page', $pages['privacy']->id, null, false, false, true, 4);
        $this->navigationItem($footerMenu, 'Voorwaarden', 'page', $pages['terms']->id, null, false, false, true, 5);
        $this->navigationItem($footerMenu, 'Contact', 'page', $pages['contact']->id, null, false, false, true, 6);
        $this->navigationItem($footerMenu, 'Klantportaal', 'custom', null, 'https://portal.example.com', true, false, true, 7);
    }

    private function navigationMenu(string $handle, string $name, int $sortOrder): NavigationMenu
    {
        $menu = NavigationMenu::query()->firstOrNew([
            'domain_id' => null,
            'handle' => $handle,
            'locale' => null,
        ]);

        if (! $menu->exists && ! $menu->uuid) {
            $menu->uuid = (string) Str::uuid();
        }

        $menu->forceFill([
            'name' => $name,
            'is_active' => true,
            'sort_order' => $sortOrder,
            'created_by' => $this->adminId,
            'updated_by' => $this->adminId,
        ])->save();

        return $menu;
    }

    private function clearNavigationItems(NavigationMenu $menu): void
    {
        NavigationMenuItem::query()
            ->where('navigation_menu_id', $menu->id)
            ->delete();
    }

    private function navigationItem(
        NavigationMenu $menu,
        string $title,
        string $linkType,
        ?int $linkId,
        ?string $customUrl,
        bool $opensNewTab,
        bool $expandChildren,
        bool $isActive,
        int $sortOrder,
        ?NavigationMenuItem $parent = null,
    ): NavigationMenuItem {
        return NavigationMenuItem::query()->create([
            'uuid' => (string) Str::uuid(),
            'navigation_menu_id' => $menu->id,
            'parent_id' => $parent?->id,
            'title' => $title,
            'link_type' => $linkType,
            'link_id' => $linkId,
            'custom_url' => $customUrl,
            'opens_new_tab' => $opensNewTab,
            'expand_children' => $expandChildren,
            'is_active' => $isActive,
            'sort_order' => $sortOrder,
            'created_by' => $this->adminId,
            'updated_by' => $this->adminId,
        ]);
    }

    /**
     * @param  list<object>  $categories
     * @return array<int, array{sort_order: int}>
     */
    private function categoryPivot(array $categories): array
    {
        $pivot = [];

        foreach (array_values($categories) as $index => $category) {
            $pivot[$category->id] = ['sort_order' => $index + 1];
        }

        return $pivot;
    }
}
