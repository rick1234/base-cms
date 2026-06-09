<?php

namespace Database\Seeders;

use App\Models\Cms\CatalogBrand;
use App\Models\Cms\CatalogCategory;
use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogProductImage;
use App\Models\Cms\CatalogProductOption;
use App\Models\Cms\CatalogProductOptionValue;
use App\Models\Cms\CatalogProductTranslation;
use App\Models\Cms\ContentAttachment;
use App\Models\Cms\ContentCategory;
use App\Models\Cms\ContentImage;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Domain;
use App\Models\Cms\Download;
use App\Models\Cms\DownloadAccessToken;
use App\Models\Cms\DownloadCategory;
use App\Models\Cms\Event;
use App\Models\Cms\EventAttachment;
use App\Models\Cms\EventCategory;
use App\Models\Cms\EventImage;
use App\Models\Cms\EventPart;
use App\Models\Cms\EventScheduleGroup;
use App\Models\Cms\FaqCategory;
use App\Models\Cms\FaqItem;
use App\Models\Cms\Form;
use App\Models\Cms\FormCategory;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\Location;
use App\Models\Cms\LocationCategory;
use App\Models\Cms\LocationOpeningHour;
use App\Models\Cms\NavigationMenu;
use App\Models\Cms\NavigationMenuItem;
use App\Models\Cms\Page;
use App\Models\Cms\Vacancy;
use App\Models\Cms\VacancyAttachment;
use App\Models\Cms\VacancyCategory;
use App\Models\Cms\WebsiteTemplate;
use App\Models\User;
use Database\Seeders\Support\SeederFiles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlatformDemoSeeder extends Seeder
{
    private ?int $adminId = null;

    public function run(): void
    {
        $this->adminId = User::query()->where('email', 'admin@example.com')->value('id');

        foreach ($this->sites() as $site) {
            $domain = $this->domain($site);
            $forms = $this->seedForms($domain, $site);
            $pages = $this->seedPages($domain, $site);
            $contentCategories = $this->seedContentCategories($site);
            $contentItems = $this->seedContentItems($domain, $site, $forms, $contentCategories);
            $catalog = $this->seedCatalog($domain, $site);
            $events = $this->seedEvents($domain, $site, $forms);
            $downloads = $this->seedDownloads($site);
            $this->seedLocations($domain, $site);
            $this->seedVacancies($domain, $site, $forms);
            $faqItems = $this->seedFaq($domain, $site);
            $navigationItems = $this->seedNavigation($domain, $site, $pages, $contentItems, $catalog, $events, $downloads);
            $this->attachFaqMoreInfoLinks($faqItems, $navigationItems, $site);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sites(): array
    {
        return [
            [
                'key' => 'local-nl',
                'host' => Domain::normalizeHost(config('cms_domains.local_domain')),
                'locale' => 'nl',
                'country_code' => 'NL',
                'name' => 'Acme Nederland',
                'company' => 'Acme Digital Works BV',
                'description' => 'Een complete Nederlandse demo website voor het Laravel CMS platform.',
                'primary_color' => '#0f6f7a',
                'secondary_color' => '#1b1b1b',
                'accent_color' => '#d86445',
                'sort_order' => 0,
                'is_development' => true,
            ],
            [
                'key' => 'nl',
                'host' => 'www.example.nl',
                'locale' => 'nl',
                'country_code' => 'NL',
                'name' => 'Acme Nederland',
                'company' => 'Acme Digital Works BV',
                'description' => 'Een complete Nederlandse demo website voor het Laravel CMS platform.',
                'primary_color' => '#1d4ed8',
                'secondary_color' => '#172554',
                'accent_color' => '#be123c',
                'sort_order' => 1,
                'is_development' => false,
            ],
            [
                'key' => 'en',
                'host' => 'www.example.com',
                'locale' => 'en',
                'country_code' => 'GB',
                'name' => 'Acme Global',
                'company' => 'Acme Digital Works Ltd',
                'description' => 'A complete English demo website for the Laravel CMS platform.',
                'primary_color' => '#0f766e',
                'secondary_color' => '#134e4a',
                'accent_color' => '#f97316',
                'sort_order' => 2,
                'is_development' => false,
            ],
            [
                'key' => 'fr',
                'host' => 'www.example.fr',
                'locale' => 'fr',
                'country_code' => 'FR',
                'name' => 'Acme France',
                'company' => 'Acme Digital Works SARL',
                'description' => 'Un site de demonstration francais complet pour la plateforme Laravel CMS.',
                'primary_color' => '#713f12',
                'secondary_color' => '#3f2a13',
                'accent_color' => '#0f766e',
                'sort_order' => 3,
                'is_development' => false,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $site
     */
    private function domain(array $site): Domain
    {
        $template = WebsiteTemplate::query()->where('handle', 'default')->first()
            ?: WebsiteTemplate::query()->first();

        $domain = Domain::query()->firstOrNew(['host' => $site['host']]);

        if (! $domain->exists && ! $domain->uuid) {
            $domain->uuid = (string) Str::uuid();
        }

        $domain->forceFill([
            'name' => $site['name'],
            'company_name' => $site['company'],
            'website_template_id' => $template?->id,
            'default_locale' => $site['locale'],
            'active_frontend_locales' => [$site['locale']],
            'active_backend_locales' => ['nl', 'en'],
            'default_meta_title' => $site['name'],
            'default_meta_description' => $site['description'],
            'default_og_title' => $site['name'],
            'default_og_description' => $site['description'],
            'title_separator' => '|',
            'template_settings' => [
                'primary_color' => $site['primary_color'],
                'secondary_color' => $site['secondary_color'],
                'accent_color' => $site['accent_color'],
                'button_style' => 'solid',
                'contact_form_placement' => 'footer',
                'social_placement' => 'footer',
            ],
            'social_links' => [
                ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/example'],
                ['platform' => 'youtube', 'label' => 'YouTube', 'url' => 'https://www.youtube.com/@example'],
            ],
            'is_active' => true,
            'is_development' => (bool) $site['is_development'],
            'sort_order' => $site['sort_order'],
            'updated_by' => $this->adminId,
        ]);

        if (! $domain->exists) {
            $domain->created_by = $this->adminId;
        }

        $domain->save();

        return $domain;
    }

    /**
     * @param  array<string, mixed>  $site
     * @return array<string, Form>
     */
    private function seedForms(Domain $domain, array $site): array
    {
        $category = FormCategory::query()->updateOrCreate(
            ['slug' => 'platform-'.$site['key'].'-forms'],
            [
                'name' => $this->copy($site, 'Formulieren', 'Forms', 'Formulaires'),
                'description' => $this->copy($site, 'Formulieren voor contact, events en sollicitaties.', 'Forms for contact, events and applications.', 'Formulaires pour contact, evenements et candidatures.'),
                'status' => 'active',
                'sort_order' => (int) $site['sort_order'] + 20,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $forms = [];

        foreach (['contact', 'quote', 'event', 'application'] as $index => $type) {
            $form = Form::query()->updateOrCreate(
                ['domain_id' => $domain->id, 'slug' => $site['key'].'-'.$type],
                [
                    'name' => $this->formLabel($site, $type),
                    'locale' => $site['locale'],
                    'description' => $this->formDescription($site, $type),
                    'submit_text' => $this->formSubmitText($site, $type),
                    'success_message' => $this->formSuccessMessage($site, $type),
                    'recipient_email' => $type.'@example.com',
                    'status' => 'published',
                    'sort_order' => $index + 1,
                    'settings' => [
                        'show_title' => true,
                        'layout' => 'default',
                        'store_submissions' => true,
                        'confirmation_email_field' => 'email',
                    ],
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );

            $form->categories()->syncWithoutDetaching([
                $category->id => ['sort_order' => $index + 1],
            ]);

            $this->seedFormStructure($form, $site, $type);
            $this->seedFormSubmission($form, $site);

            $forms[$type] = $form;
        }

        $domain->forceFill(['contact_form_id' => $forms['contact']->id])->save();

        return $forms;
    }

    /**
     * @param  array<string, mixed>  $site
     */
    private function seedFormStructure(Form $form, array $site, string $type): void
    {
        $form->recipients()->updateOrCreate(
            ['email' => $form->recipient_email, 'type' => 'to'],
            [
                'name' => $form->name.' team',
                'is_active' => true,
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $form->messages()->updateOrCreate(
            ['type' => 'confirmation'],
            [
                'name' => $this->copy($site, 'Bevestigingsmail', 'Confirmation mail', 'E-mail de confirmation'),
                'subject' => $this->copy($site, 'We hebben uw inzending ontvangen', 'We received your submission', 'Nous avons recu votre envoi'),
                'body' => $this->copy($site, "Beste {name},\n\nBedankt voor uw bericht via {form_name}.\n\n{{summary}}", "Hi {name},\n\nThanks for your message through {form_name}.\n\n{{summary}}", "Bonjour {name},\n\nMerci pour votre message via {form_name}.\n\n{{summary}}"),
                'is_active' => true,
                'sort_order' => 1,
                'settings' => ['layout' => 'default'],
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $form->messages()->updateOrCreate(
            ['type' => 'notification'],
            [
                'name' => $this->copy($site, 'Interne melding', 'Internal notification', 'Notification interne'),
                'subject' => $this->copy($site, 'Nieuwe inzending: {form_name}', 'New submission: {form_name}', 'Nouvel envoi: {form_name}'),
                'body' => '{{summary}}',
                'is_active' => true,
                'sort_order' => 2,
                'settings' => ['layout' => 'default'],
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        if ($form->blocks()->exists()) {
            return;
        }

        $block = $form->blocks()->create([
            'title' => $this->copy($site, 'Gegevens', 'Details', 'Coordonnees'),
            'sort_order' => 1,
            'created_by' => $this->adminId,
            'updated_by' => $this->adminId,
        ]);

        $introRow = $block->rows()->create(['sort_order' => 1, 'created_by' => $this->adminId, 'updated_by' => $this->adminId]);
        $this->field($introRow, 'form_intro', $this->formLabel($site, $type), 'title', false, 100, 1);
        $this->field($introRow, 'form_help', $this->formDescription($site, $type), 'paragraph', false, 100, 2);

        $contactRow = $block->rows()->create(['sort_order' => 2, 'created_by' => $this->adminId, 'updated_by' => $this->adminId]);
        $this->field($contactRow, 'name', $this->copy($site, 'Naam', 'Name', 'Nom'), 'input', true, 50, 1, ['placeholder' => $this->copy($site, 'Uw naam', 'Your name', 'Votre nom')]);
        $this->field($contactRow, 'email', $this->copy($site, 'E-mail', 'Email', 'E-mail'), 'email', true, 50, 2, ['placeholder' => 'name@example.com']);
        $this->field($contactRow, 'phone', $this->copy($site, 'Telefoon', 'Phone', 'Telephone'), 'phone', false, 50, 3);
        $this->field($contactRow, 'company', $this->copy($site, 'Organisatie', 'Organisation', 'Organisation'), 'input', false, 50, 4);

        $detailsRow = $block->rows()->create(['sort_order' => 3, 'created_by' => $this->adminId, 'updated_by' => $this->adminId]);
        $subject = $this->field($detailsRow, 'subject', $this->copy($site, 'Onderwerp', 'Subject', 'Sujet'), 'select', true, 50, 1);
        foreach ($this->selectOptions($site) as $value => $label) {
            $subject->options()->create(['label' => $label, 'value' => $value, 'sort_order' => $subject->options()->count() + 1, 'created_by' => $this->adminId, 'updated_by' => $this->adminId]);
        }
        $this->field($detailsRow, 'date', $this->copy($site, 'Gewenste datum', 'Preferred date', 'Date souhaitee'), 'date', false, 25, 2);
        $this->field($detailsRow, 'participants', $this->copy($site, 'Aantal personen', 'Number of people', 'Nombre de personnes'), 'number', false, 25, 3);

        $messageRow = $block->rows()->create(['sort_order' => 4, 'created_by' => $this->adminId, 'updated_by' => $this->adminId]);
        $this->field($messageRow, 'message', $this->copy($site, 'Bericht', 'Message', 'Message'), 'textarea', true, 100, 1);
        $this->field($messageRow, 'attachment', $this->copy($site, 'Bijlage', 'Attachment', 'Piece jointe'), 'file', false, 50, 2);
        $consent = $this->field($messageRow, 'consent', $this->copy($site, 'Akkoord', 'Consent', 'Accord'), 'checkbox', true, 50, 3);
        $consent->options()->create([
            'label' => $this->copy($site, 'Ik ga akkoord met de verwerking van mijn gegevens.', 'I agree to the processing of my details.', 'J accepte le traitement de mes donnees.'),
            'value' => 'yes',
            'sort_order' => 1,
            'created_by' => $this->adminId,
            'updated_by' => $this->adminId,
        ]);
    }

    private function field(
        mixed $row,
        string $name,
        string $label,
        string $type,
        bool $required,
        int $width,
        int $sortOrder,
        array $settings = [],
    ): mixed {
        return $row->fields()->create([
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'is_required' => $required,
            'sort_order' => $sortOrder,
            'settings' => ['label_visible' => true, 'width' => $width, ...$settings],
            'created_by' => $this->adminId,
            'updated_by' => $this->adminId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $site
     */
    private function seedFormSubmission(Form $form, array $site): void
    {
        if ($form->submissions()->exists()) {
            return;
        }

        $payload = [
            'name' => $this->copy($site, 'Demo bezoeker', 'Demo visitor', 'Visiteur demo'),
            'email' => 'demo-'.$site['key'].'@example.com',
            'phone' => '+31 30 000 0000',
            'company' => $site['company'],
            'subject' => 'project',
            'date' => now()->addWeeks(2)->toDateString(),
            'participants' => '3',
            'message' => $this->copy($site, 'Deze seeded inzending laat het berichtenoverzicht zien.', 'This seeded submission fills the received messages overview.', 'Cet envoi de demonstration remplit la liste des messages recus.'),
            'consent' => 'yes',
        ];

        $submission = FormSubmission::query()->create([
            'form_id' => $form->id,
            'locale' => $site['locale'],
            'remote_ip' => '203.0.113.'.((int) $site['sort_order'] + 10),
            'user_agent' => 'Seeder demo browser',
            'payload' => $payload,
            'created_by' => $this->adminId,
            'updated_by' => $this->adminId,
        ]);

        foreach ($form->blocks()->with('rows.fields')->get()->flatMap(fn ($block) => $block->rows)->flatMap(fn ($row) => $row->fields) as $field) {
            if (! $field->acceptsSubmissionValue()) {
                continue;
            }

            $submission->answers()->create([
                'field_id' => $field->id,
                'field_name' => $field->name,
                'value' => $payload[$field->name] ?? null,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $site
     * @return array<string, Page>
     */
    private function seedPages(Domain $domain, array $site): array
    {
        $records = [
            'home' => [
                'title' => $site['name'],
                'navigation_label' => $this->copy($site, 'Home', 'Home', 'Accueil'),
                'excerpt' => $this->copy($site, 'Een complete demo met pagina blokken, events, downloads en formulieren.', 'A complete demo with page blocks, events, downloads and forms.', 'Une demo complete avec blocs, evenements, telechargements et formulaires.'),
                'body' => $this->copy($site, 'Deze homepagina is domein-gebonden. De navigatie verwijst naar rijk gevulde contentpagina\'s in de paginamodule.', 'This homepage is scoped to the domain. The navigation points to richly filled pages in the page module.', 'Cette page d accueil est liee au domaine. La navigation mene vers des pages riches dans le module pages.'),
                'sort_order' => 0,
            ],
            'privacy' => [
                'title' => $this->copy($site, 'Privacy', 'Privacy', 'Confidentialite'),
                'navigation_label' => $this->copy($site, 'Privacy', 'Privacy', 'Confidentialite'),
                'excerpt' => $this->copy($site, 'Voorbeeld privacy pagina.', 'Sample privacy page.', 'Page de confidentialite exemple.'),
                'body' => $this->copy($site, 'Deze demo toont hoe statische juridische pagina\'s per website kunnen worden beheerd.', 'This demo shows how static legal pages can be managed per website.', 'Cette demo montre comment gerer les pages juridiques par site.'),
                'sort_order' => 90,
            ],
        ];

        $pages = [];

        foreach ($records as $slug => $record) {
            $pages[$slug] = Page::query()->updateOrCreate(
                ['domain_id' => $domain->id, 'slug' => $slug],
                [
                    'uuid' => (string) Str::uuid(),
                    'title' => $record['title'],
                    'navigation_label' => $record['navigation_label'],
                    'excerpt' => $record['excerpt'],
                    'body' => $record['body'],
                    'meta_title' => $record['title'],
                    'meta_description' => $record['excerpt'],
                    'template' => 'default',
                    'status' => 'published',
                    'sort_order' => $record['sort_order'],
                    'published_at' => now()->subDay(),
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );
        }

        return $pages;
    }

    /**
     * @param  array<string, mixed>  $site
     * @return array<string, ContentCategory>
     */
    private function seedContentCategories(array $site): array
    {
        $root = ContentCategory::query()->updateOrCreate(
            ['slug' => 'platform-'.$site['key'].'-pages'],
            [
                'name' => $this->copy($site, 'Pagina\'s', 'Pages', 'Pages'),
                'description' => $this->copy($site, 'Domein content voor de paginamodule.', 'Domain content for the page module.', 'Contenu de domaine pour le module pages.'),
                'status' => 'active',
                'is_hidden_from_navigation' => false,
                'sort_order' => (int) $site['sort_order'] + 20,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $services = ContentCategory::query()->updateOrCreate(
            ['slug' => 'platform-'.$site['key'].'-services'],
            [
                'parent_id' => $root->id,
                'name' => $this->copy($site, 'Diensten', 'Services', 'Services'),
                'description' => $this->copy($site, 'Servicepagina\'s en cases.', 'Service pages and cases.', 'Pages de services et cas.'),
                'status' => 'active',
                'is_hidden_from_navigation' => false,
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        return ['root' => $root, 'services' => $services];
    }

    /**
     * @param  array<string, mixed>  $site
     * @param  array<string, Form>  $forms
     * @param  array<string, ContentCategory>  $categories
     * @return array<string, ContentItem>
     */
    private function seedContentItems(Domain $domain, array $site, array $forms, array $categories): array
    {
        $records = [
            'services' => [
                'title' => $this->copy($site, 'Diensten voor groeiende teams', 'Services for growing teams', 'Services pour les equipes en croissance'),
                'subtitle' => $this->copy($site, 'Strategie, websites, koppelingen en beheer.', 'Strategy, websites, integrations and care.', 'Strategie, sites web, integrations et support.'),
                'form' => 'quote',
                'sort_order' => 1,
            ],
            'cases' => [
                'title' => $this->copy($site, 'Cases en resultaten', 'Cases and outcomes', 'Cas clients et resultats'),
                'subtitle' => $this->copy($site, 'Voorbeeldprojecten met blokken, media en downloads.', 'Example projects with blocks, media and downloads.', 'Projets exemples avec blocs, medias et telechargements.'),
                'form' => 'contact',
                'sort_order' => 2,
            ],
            'knowledge' => [
                'title' => $this->copy($site, 'Kennisbank voor redacties', 'Knowledge base for editors', 'Base de connaissances pour redacteurs'),
                'subtitle' => $this->copy($site, 'Handleidingen, training en praktische checks.', 'Guides, training and practical checks.', 'Guides, formation et controles pratiques.'),
                'form' => 'event',
                'sort_order' => 3,
            ],
            'contact' => [
                'title' => $this->copy($site, 'Contact opnemen', 'Get in touch', 'Nous contacter'),
                'subtitle' => $this->copy($site, 'Gebruik het formulier in de footer of stuur direct een bericht.', 'Use the footer form or send a direct message.', 'Utilisez le formulaire en pied de page ou envoyez un message direct.'),
                'form' => 'contact',
                'sort_order' => 4,
            ],
        ];

        $items = [];

        foreach ($records as $slug => $record) {
            $item = ContentItem::query()->updateOrCreate(
                ['domain_id' => $domain->id, 'slug' => $slug, 'locale' => $site['locale']],
                [
                    'title' => $record['title'],
                    'subtitle' => $record['subtitle'],
                    'meta_description' => $record['subtitle'],
                    'form_id' => $forms[$record['form']]->id,
                    'structured_blocks' => $this->structuredBlocks($site, $slug),
                    'status' => 'published',
                    'active_from' => now()->subDay()->toDateString(),
                    'active_until' => now()->addYear()->toDateString(),
                    'sort_order' => $record['sort_order'],
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );

            $item->categories()->sync($this->categoryPivot([$categories['root'], $categories['services']]));
            $this->seedContentMedia($item, $site, $slug);
            $items[$slug] = $item;
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $site
     */
    private function seedContentMedia(ContentItem $item, array $site, string $slug): void
    {
        $image = SeederFiles::publicImage($this->imageFixture((int) $site['sort_order'] + 1), 'platform-demo/'.$site['key'].'/content-images', $slug.'.jpg');
        $attachment = SeederFiles::publicDocument('website-launch-checklist.txt', 'platform-demo/'.$site['key'].'/content-attachments', $slug.'-checklist.txt');

        ContentImage::query()->updateOrCreate(
            ['content_item_id' => $item->id, 'image_path' => $image],
            [
                'folder' => 'storage/platform-demo/'.$site['key'].'/content-images/',
                'caption' => $item->title,
                'original_filename' => basename($image),
                'mime_type' => 'image/jpeg',
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        ContentAttachment::query()->updateOrCreate(
            ['content_item_id' => $item->id, 'url' => $attachment],
            [
                'name' => $this->copy($site, 'Checklist', 'Checklist', 'Checklist'),
                'type' => 'text/plain',
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $site
     * @return array<string, mixed>
     */
    private function seedCatalog(Domain $domain, array $site): array
    {
        $category = CatalogCategory::query()->updateOrCreate(
            ['slug' => 'platform-'.$site['key'].'-catalog'],
            [
                'name' => $this->copy($site, 'Catalogus', 'Catalog', 'Catalogue'),
                'description' => $this->copy($site, 'Demo producten en servicepakketten.', 'Demo products and service packages.', 'Produits demo et forfaits de service.'),
                'status' => 'active',
                'sort_order' => (int) $site['sort_order'] + 20,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $brand = CatalogBrand::query()->updateOrCreate(
            ['slug' => 'platform-'.$site['key'].'-brand'],
            [
                'name' => $site['name'],
                'description' => $site['company'],
                'website_url' => 'https://'.$site['host'],
                'intro' => $this->copy($site, 'Demo merkprofiel voor de catalogus.', 'Demo brand profile for the catalog.', 'Profil de marque demo pour le catalogue.'),
                'body' => $this->copy($site, 'Dit merk demonstreert extra catalogusvelden zonder webshopfunctionaliteit.', 'This brand demonstrates extended catalog fields without webshop functionality.', 'Cette marque montre les champs de catalogue etendus sans fonctionnalite boutique.'),
                'meta_title' => $site['name'],
                'meta_description' => $site['company'],
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $product = CatalogProduct::query()->updateOrCreate(
            ['domain_id' => $domain->id, 'sku' => strtoupper($site['key']).'-CMS-CARE'],
            [
                'name' => $this->copy($site, 'CMS care pakket', 'CMS care package', 'Forfait support CMS'),
                'description' => $this->copy($site, 'Een seeded product met afbeeldingen, opties en vertalingen.', 'A seeded product with images, options and translations.', 'Un produit demo avec images, options et traductions.'),
                'price' => 249500,
                'meta_description' => $this->copy($site, 'Demo product voor het CMS catalogusbeheer.', 'Demo product for CMS catalog management.', 'Produit demo pour la gestion du catalogue CMS.'),
                'brand_id' => $brand->id,
                'status' => 'published',
                'active_from' => now()->subDay()->toDateString(),
                'active_until' => now()->addYear()->toDateString(),
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $product->categories()->sync($this->categoryPivot([$category]));

        $image = SeederFiles::publicImage($this->imageFixture((int) $site['sort_order'] + 2), 'platform-demo/'.$site['key'].'/catalog', 'cms-care.jpg');
        CatalogProductImage::query()->updateOrCreate(
            ['catalog_product_id' => $product->id, 'image_path' => $image],
            [
                'folder' => 'storage/platform-demo/'.$site['key'].'/catalog/',
                'caption' => $product->name,
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $option = CatalogProductOption::query()->updateOrCreate(
            ['catalog_product_id' => $product->id, 'label' => $this->copy($site, 'Inbegrepen', 'Included', 'Inclus')],
            [
                'label_translations' => [
                    $site['locale'] => $this->copy($site, 'Inbegrepen', 'Included', 'Inclus'),
                ],
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        CatalogProductOptionValue::query()->updateOrCreate(
            ['catalog_product_option_id' => $option->id, 'value' => $this->copy($site, 'Updates, monitoring, support en contentadvies.', 'Updates, monitoring, support and content advice.', 'Mises a jour, surveillance, support et conseil contenu.')],
            [
                'value_translations' => [
                    $site['locale'] => $this->copy($site, 'Updates, monitoring, support en contentadvies.', 'Updates, monitoring, support and content advice.', 'Mises a jour, surveillance, support et conseil contenu.'),
                ],
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        CatalogProductTranslation::query()->updateOrCreate(
            ['catalog_product_id' => $product->id, 'locale' => $site['locale']],
            [
                'title' => $product->name,
                'subtitle' => $this->copy($site, 'Catalogusproduct', 'Catalog product', 'Produit catalogue'),
                'content' => $product->description,
                'button_text' => $this->copy($site, 'Meer informatie', 'More information', 'Plus d informations'),
                'link_url' => '/services',
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        return ['category' => $category, 'product' => $product];
    }

    /**
     * @param  array<string, mixed>  $site
     * @param  array<string, Form>  $forms
     * @return array<string, Event>
     */
    private function seedEvents(Domain $domain, array $site, array $forms): array
    {
        $category = EventCategory::query()->updateOrCreate(
            ['slug' => 'platform-'.$site['key'].'-events'],
            [
                'name' => $this->copy($site, 'Evenementen', 'Events', 'Evenements'),
                'description' => $this->copy($site, 'Trainingen, workshops en demo dagen.', 'Training, workshops and demo days.', 'Formations, ateliers et journees demo.'),
                'status' => 'active',
                'sort_order' => (int) $site['sort_order'] + 20,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $event = Event::query()->updateOrCreate(
            ['domain_id' => $domain->id, 'slug' => 'editor-demo-day', 'locale' => $site['locale']],
            [
                'title' => $this->copy($site, 'Redactie demo dag', 'Editor demo day', 'Journee demo redacteur'),
                'subtitle' => $this->copy($site, 'Leer de modules, blokken en workflow kennen.', 'Learn the modules, blocks and workflow.', 'Decouvrir les modules, blocs et le flux de travail.'),
                'body' => $this->copy($site, 'Een complete seeded eventpagina met tijdschema, fotoalbum, formulier en bijlage.', 'A complete seeded event page with schedule, album, form and attachment.', 'Une page evenement demo avec programme, album, formulaire et piece jointe.'),
                'structured_blocks' => $this->structuredBlocks($site, 'event'),
                'meta_description' => $this->copy($site, 'Demo event voor het CMS eventbeheer.', 'Demo event for CMS event management.', 'Evenement demo pour la gestion CMS.'),
                'form_id' => $forms['event']->id,
                'starts_at' => now()->addMonth()->toDateString(),
                'ends_at' => now()->addMonth()->addDay()->toDateString(),
                'status' => 'published',
                'active_from' => now()->subDay()->toDateString(),
                'active_until' => now()->addYear()->toDateString(),
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $event->categories()->sync($this->categoryPivot([$category]));

        $group = EventScheduleGroup::query()->updateOrCreate(
            ['event_id' => $event->id, 'name' => $this->copy($site, 'Tijdschema', 'Schedule', 'Programme')],
            [
                'sort_order' => 1,
                'is_collapsed' => false,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        foreach ([
            ['09:30', $this->copy($site, 'Ontvangst en doelen', 'Welcome and goals', 'Accueil et objectifs')],
            ['11:00', $this->copy($site, 'Blokken bouwen', 'Building blocks', 'Construction des blocs')],
            ['14:00', $this->copy($site, 'Publiceren en controleren', 'Publishing and review', 'Publication et controle')],
        ] as $index => [$time, $title]) {
            EventPart::query()->updateOrCreate(
                ['event_id' => $event->id, 'title' => $title],
                [
                    'event_schedule_group_id' => $group->id,
                    'content' => $this->copy($site, 'Seeded onderdeel van het programma.', 'Seeded schedule item.', 'Element de programme demo.'),
                    'starts_at' => now()->addMonth()->setTimeFromTimeString($time),
                    'ends_at' => now()->addMonth()->setTimeFromTimeString($time)->addHour(),
                    'sort_order' => $index + 1,
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );
        }

        $image = SeederFiles::publicImage($this->imageFixture((int) $site['sort_order'] + 3), 'platform-demo/'.$site['key'].'/events', 'event.jpg');
        EventImage::query()->updateOrCreate(
            ['event_id' => $event->id, 'image_path' => $image],
            [
                'folder' => 'storage/platform-demo/'.$site['key'].'/events/',
                'caption' => $event->title,
                'alt_text' => $event->title,
                'title_text' => $event->title,
                'description' => $event->subtitle,
                'credit' => 'Seeder',
                'is_decorative' => false,
                'original_filename' => basename($image),
                'mime_type' => 'image/jpeg',
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $attachment = SeederFiles::publicDocument('event-attachment.txt', 'platform-demo/'.$site['key'].'/events', 'event-program.txt');
        EventAttachment::query()->updateOrCreate(
            ['event_id' => $event->id, 'url' => $attachment],
            [
                'name' => $this->copy($site, 'Programma', 'Program', 'Programme'),
                'type' => 'text/plain',
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        return ['main' => $event];
    }

    /**
     * @param  array<string, mixed>  $site
     * @return array<string, Download>
     */
    private function seedDownloads(array $site): array
    {
        $category = DownloadCategory::query()->updateOrCreate(
            ['slug' => 'platform-'.$site['key'].'-downloads'],
            [
                'name' => $this->copy($site, 'Downloads', 'Downloads', 'Telechargements'),
                'description' => $this->copy($site, 'Publieke, beveiligde en uitnodigingsdownloads.', 'Public, protected and invite downloads.', 'Telechargements publics, proteges et invites.'),
                'status' => 'active',
                'sort_order' => (int) $site['sort_order'] + 20,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $records = [
            'public' => ['fixture' => 'demo-service-guide.txt', 'protected' => false],
            'protected' => ['fixture' => 'protected-project-brief.txt', 'protected' => true],
        ];
        $downloads = [];

        foreach ($records as $type => $record) {
            $file = SeederFiles::privateDocument($record['fixture'], 'platform-demo/'.$site['key'].'/downloads', $type.'-'.$site['key'].'.txt');
            $download = Download::query()->updateOrCreate(
                ['slug' => 'platform-'.$site['key'].'-'.$type.'-download'],
                [
                    'name' => $this->downloadName($site, $type),
                    'description' => $this->copy($site, 'Bestand wordt via de downloadmodule aangeboden.', 'File is served through the download module.', 'Le fichier est servi via le module telechargement.'),
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
                    'is_password_protected' => (bool) $record['protected'],
                    'password_hash' => $record['protected'] ? Hash::make('demo-download') : null,
                    'link_expires_after_minutes' => 120,
                    'download_count' => $type === 'public' ? 7 : 2,
                    'last_downloaded_at' => now()->subHours(6),
                    'sort_order' => $type === 'public' ? 1 : 2,
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );

            $download->categories()->sync($this->categoryPivot([$category]));
            $this->seedDownloadToken($download, $site, $type);
            $downloads[$type] = $download;
        }

        return $downloads;
    }

    /**
     * @param  array<string, mixed>  $site
     */
    private function seedDownloadToken(Download $download, array $site, string $type): void
    {
        DownloadAccessToken::query()->updateOrCreate(
            ['token_hash' => hash('sha256', 'platform-'.$site['key'].'-'.$type.'-download-token')],
            [
                'uuid' => (string) Str::uuid(),
                'download_id' => $download->id,
                'email' => 'download-'.$site['key'].'@example.com',
                'purpose' => 'invite',
                'created_by' => $this->adminId,
                'expires_at' => now()->addMonth(),
                'used_count' => $type === 'public' ? 3 : 1,
                'last_used_at' => now()->subDays(2),
                'first_ip_address' => '198.51.100.'.((int) $site['sort_order'] + 10),
                'last_ip_address' => '198.51.100.'.((int) $site['sort_order'] + 20),
                'last_user_agent' => 'Seeder download client',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $site
     */
    private function seedLocations(Domain $domain, array $site): void
    {
        $category = LocationCategory::query()->updateOrCreate(
            ['slug' => 'platform-'.$site['key'].'-locations'],
            [
                'name' => $this->copy($site, 'Vestigingen', 'Locations', 'Implantations'),
                'description' => $this->copy($site, 'Kantoren en demo locaties.', 'Offices and demo locations.', 'Bureaux et lieux de demo.'),
                'status' => 'active',
                'sort_order' => (int) $site['sort_order'] + 20,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $location = Location::query()->updateOrCreate(
            ['domain_id' => $domain->id, 'name' => $site['name'].' HQ'],
            [
                'street_address' => $this->copy($site, 'Demostraat 10', 'Demo Street 10', 'Rue Demo 10'),
                'postal_code' => $this->copy($site, '1011 AA', 'EC1A 1AA', '75001'),
                'city' => $this->copy($site, 'Amsterdam', 'London', 'Paris'),
                'country_code' => $site['country_code'],
                'email' => 'hello-'.$site['key'].'@example.com',
                'phone' => '+31 30 000 0000',
                'website_url' => 'https://'.$site['host'],
                'chamber_of_commerce_number' => 'DEMO-'.$site['key'],
                'description' => $this->copy($site, '<p>Seeded vestiging met openingstijden en locatiegegevens.</p>', '<p>Seeded location with opening hours and map details.</p>', '<p>Implantation demo avec horaires et informations de localisation.</p>'),
                'latitude' => $this->copy($site, '52.3676', '51.5072', '48.8566'),
                'longitude' => $this->copy($site, '4.9041', '-0.1276', '2.3522'),
                'map_info' => $this->copy($site, 'Gebruik dit veld voor iframe of locatie-informatie.', 'Use this field for iframe or location information.', 'Utilisez ce champ pour iframe ou informations de localisation.'),
                'status' => 'active',
                'active_from' => now()->subDay()->toDateString(),
                'active_until' => now()->addYear()->toDateString(),
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $location->categories()->sync($this->categoryPivot([$category]));

        foreach (array_keys(Location::dayNames()) as $day) {
            $closed = in_array($day, ['5', '6'], true);
            LocationOpeningHour::query()->updateOrCreate(
                ['location_id' => $location->id, 'day' => $day],
                [
                    'opens_at' => $closed ? null : '09:00',
                    'closes_at' => $closed ? null : '17:30',
                    'is_closed' => $closed,
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $site
     * @param  array<string, Form>  $forms
     */
    private function seedVacancies(Domain $domain, array $site, array $forms): void
    {
        $category = VacancyCategory::query()->updateOrCreate(
            ['slug' => 'platform-'.$site['key'].'-vacancies'],
            [
                'name' => $this->copy($site, 'Vacatures', 'Vacancies', 'Offres d emploi'),
                'description' => $this->copy($site, 'Seeded vacatures met formulierkoppeling.', 'Seeded vacancies with form links.', 'Offres demo avec formulaire lie.'),
                'status' => 'active',
                'sort_order' => (int) $site['sort_order'] + 20,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $vacancy = Vacancy::query()->updateOrCreate(
            ['domain_id' => $domain->id, 'slug' => 'cms-specialist', 'locale' => $site['locale']],
            [
                'title' => $this->copy($site, 'CMS specialist', 'CMS specialist', 'Specialiste CMS'),
                'body' => $this->copy($site, '<p>Werk aan maatwerkwebsites, modules en content workflows.</p>', '<p>Work on custom websites, modules and content workflows.</p>', '<p>Travaillez sur des sites sur mesure, modules et flux de contenu.</p>'),
                'image_path' => SeederFiles::publicImage($this->imageFixture((int) $site['sort_order'] + 4), 'platform-demo/'.$site['key'].'/vacancies', 'vacancy.jpg'),
                'meta_description' => $this->copy($site, 'Seeded vacature met sollicitatieformulier.', 'Seeded vacancy with application form.', 'Offre demo avec formulaire de candidature.'),
                'form_id' => $forms['application']->id,
                'status' => 'published',
                'active_from' => now()->subDay()->toDateString(),
                'active_until' => now()->addMonths(6)->toDateString(),
                'sort_order' => 1,
                'metadata' => [
                    'location' => $this->copy($site, 'Hybride', 'Hybrid', 'Hybride'),
                    'hours' => '32-40',
                    'contract' => $this->copy($site, 'Vast', 'Permanent', 'CDI'),
                ],
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $vacancy->categories()->sync($this->categoryPivot([$category]));

        $attachment = SeederFiles::publicDocument('client-onboarding-pack.txt', 'platform-demo/'.$site['key'].'/vacancies', 'vacancy-profile.txt');
        VacancyAttachment::query()->updateOrCreate(
            ['vacancy_id' => $vacancy->id, 'url' => $attachment],
            [
                'name' => $this->copy($site, 'Functieprofiel', 'Role profile', 'Profil du poste'),
                'type' => 'text/plain',
                'sort_order' => 1,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $site
     * @return array<string, FaqItem>
     */
    private function seedFaq(Domain $domain, array $site): array
    {
        $category = FaqCategory::query()->updateOrCreate(
            ['slug' => 'platform-'.$site['key'].'-faq'],
            [
                'name' => 'FAQ',
                'description' => $this->copy($site, 'Veelgestelde vragen voor deze website.', 'Frequently asked questions for this website.', 'Questions frequentes pour ce site.'),
                'status' => 'active',
                'sort_order' => (int) $site['sort_order'] + 20,
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ],
        );

        $items = [];

        foreach ([
            'content' => [
                'question' => $this->copy($site, 'Kan ik pagina\'s met blokken bouwen?', 'Can I build pages with blocks?', 'Puis-je creer des pages avec des blocs?'),
                'answer' => $this->copy($site, '<p>Ja. De paginamodule bevat tekst, afbeeldingen, gallery, video, quote, button en bijlage blokken.</p>', '<p>Yes. The page module includes text, image, gallery, video, quote, button and attachment blocks.</p>', '<p>Oui. Le module pages contient des blocs texte, image, galerie, video, citation, bouton et piece jointe.</p>'),
            ],
            'downloads' => [
                'question' => $this->copy($site, 'Zijn downloads beveiligd?', 'Are downloads protected?', 'Les telechargements sont-ils proteges?'),
                'answer' => $this->copy($site, '<p>Downloads worden via de module aangeboden, met wachtwoorden en unieke uitnodigingslinks.</p>', '<p>Downloads are served through the module, with passwords and unique invite links.</p>', '<p>Les telechargements passent par le module, avec mots de passe et liens uniques.</p>'),
            ],
        ] as $slug => $record) {
            $faq = FaqItem::query()->updateOrCreate(
                ['domain_id' => $domain->id, 'slug' => $slug, 'locale' => $site['locale']],
                [
                    'question' => $record['question'],
                    'body' => $record['answer'],
                    'meta_title' => $record['question'],
                    'meta_description' => strip_tags($record['answer']),
                    'status' => 'published',
                    'active_from' => now()->subDay()->toDateString(),
                    'active_until' => now()->addYear()->toDateString(),
                    'sort_order' => count($items) + 1,
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                ],
            );

            $faq->categories()->sync($this->categoryPivot([$category]));
            $items[$slug] = $faq;
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $site
     * @param  array<string, Page>  $pages
     * @param  array<string, ContentItem>  $contentItems
     * @param  array<string, mixed>  $catalog
     * @param  array<string, Event>  $events
     * @param  array<string, Download>  $downloads
     * @return array<string, NavigationMenuItem>
     */
    private function seedNavigation(
        Domain $domain,
        array $site,
        array $pages,
        array $contentItems,
        array $catalog,
        array $events,
        array $downloads,
    ): array {
        $primary = $this->navigationMenu($domain, $site, 'primary', $this->copy($site, 'Hoofdnavigatie', 'Primary navigation', 'Navigation principale'), 1);
        $footer = $this->navigationMenu($domain, $site, 'footer', $this->copy($site, 'Voeternavigatie', 'Footer navigation', 'Navigation pied de page'), 2);

        $this->clearNavigationItems($primary);
        $this->clearNavigationItems($footer);

        $items = [];
        $items['home'] = $this->navigationItem($primary, $this->copy($site, 'Home', 'Home', 'Accueil'), 'page', $pages['home']->id, null, false, false, true, 1);
        $items['services'] = $this->navigationItem($primary, $this->copy($site, 'Diensten', 'Services', 'Services'), 'content_item', $contentItems['services']->id, null, false, false, true, 2);
        $items['cases'] = $this->navigationItem($primary, $this->copy($site, 'Cases', 'Cases', 'Cas'), 'content_item', $contentItems['cases']->id, null, false, false, true, 3);
        $items['catalog'] = $this->navigationItem($primary, $this->copy($site, 'Catalogus', 'Catalog', 'Catalogue'), 'catalog_category', $catalog['category']->id, null, false, true, true, 4);
        $items['event'] = $this->navigationItem($primary, $this->copy($site, 'Event', 'Event', 'Evenement'), 'event', $events['main']->id, null, false, false, true, 5);
        $items['download'] = $this->navigationItem($primary, $this->copy($site, 'Download', 'Download', 'Telechargement'), 'download', $downloads['public']->id, null, false, false, true, 6);
        $items['contact'] = $this->navigationItem($primary, $this->copy($site, 'Contact', 'Contact', 'Contact'), 'content_item', $contentItems['contact']->id, null, false, false, true, 7);
        $items['portal'] = $this->navigationItem($primary, $this->copy($site, 'Klantportaal', 'Client portal', 'Portail client'), 'custom', null, 'https://portal.example.com', true, false, true, 8);

        $this->navigationItem($footer, $this->copy($site, 'Privacy', 'Privacy', 'Confidentialite'), 'page', $pages['privacy']->id, null, false, false, true, 1);
        $this->navigationItem($footer, $this->copy($site, 'Kennisbank', 'Knowledge base', 'Base de connaissances'), 'content_item', $contentItems['knowledge']->id, null, false, false, true, 2);
        $this->navigationItem($footer, $this->copy($site, 'Beveiligde download', 'Protected download', 'Telechargement protege'), 'download', $downloads['protected']->id, null, false, false, true, 3);

        return $items;
    }

    /**
     * @param  array<string, mixed>  $site
     */
    private function navigationMenu(Domain $domain, array $site, string $handle, string $name, int $sortOrder): NavigationMenu
    {
        $menu = NavigationMenu::query()->firstOrNew([
            'domain_id' => $domain->id,
            'handle' => $handle,
            'locale' => $site['locale'],
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
     * @param  array<string, FaqItem>  $faqItems
     * @param  array<string, NavigationMenuItem>  $navigationItems
     * @param  array<string, mixed>  $site
     */
    private function attachFaqMoreInfoLinks(array $faqItems, array $navigationItems, array $site): void
    {
        foreach ($faqItems as $key => $faq) {
            $targets = $key === 'downloads'
                ? [$navigationItems['download'], $navigationItems['contact']]
                : [$navigationItems['services'], $navigationItems['event']];

            $links = collect($targets)
                ->map(fn (NavigationMenuItem $item): array => [
                    'navigation_item_id' => $item->id,
                    'label' => $this->copy($site, 'Meer informatie', 'More information', 'Plus d informations'),
                ])
                ->all();

            $faq->forceFill([
                'metadata' => [
                    'more_info_links' => $links,
                    'more_info' => $links[0],
                ],
            ])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $site
     * @return list<array<string, mixed>>
     */
    private function structuredBlocks(array $site, string $context): array
    {
        $image = SeederFiles::publicImage($this->imageFixture((int) $site['sort_order'] + 5), 'platform-demo/'.$site['key'].'/blocks', $context.'-image.jpg');
        $galleryA = SeederFiles::publicImage($this->imageFixture((int) $site['sort_order'] + 6), 'platform-demo/'.$site['key'].'/blocks', $context.'-gallery-a.jpg');
        $galleryB = SeederFiles::publicImage($this->imageFixture((int) $site['sort_order'] + 7), 'platform-demo/'.$site['key'].'/blocks', $context.'-gallery-b.jpg');
        $attachment = SeederFiles::publicDocument('website-launch-checklist.txt', 'platform-demo/'.$site['key'].'/blocks', $context.'-attachment.txt');

        return [
            [
                'type' => 'title',
                'uuid' => (string) Str::uuid(),
                'layout' => '100',
                'data' => [
                    'title' => $this->copy($site, 'Blokken die direct renderen', 'Blocks that render immediately', 'Des blocs visibles immediatement'),
                    'level' => 'h2',
                ],
                'settings' => ['alignment' => 'left', 'anchor' => $context.'-blocks'],
            ],
            [
                'type' => 'text',
                'uuid' => (string) Str::uuid(),
                'layout' => '100',
                'data' => [
                    'content' => $this->copy($site, '<p>Deze pagina is gevuld met meerdere page builder blokken, zodat editors meteen kunnen zien hoe content, media en call-to-actions samenwerken.</p>', '<p>This page is filled with several page builder blocks so editors can see how content, media and calls to action work together.</p>', '<p>Cette page contient plusieurs blocs pour montrer comment contenu, medias et appels a l action fonctionnent ensemble.</p>'),
                ],
                'settings' => ['alignment' => 'left', 'background_style' => 'muted', 'intro_style' => true],
            ],
            [
                'type' => 'image',
                'uuid' => (string) Str::uuid(),
                'layout' => '50',
                'data' => [
                    'image' => $image,
                    'alt' => $this->copy($site, 'Demo afbeelding', 'Demo image', 'Image demo'),
                    'caption' => $this->copy($site, 'Afbeelding met bijschrift', 'Image with caption', 'Image avec legende'),
                    'link_url' => '/contact',
                ],
                'settings' => ['layout' => 'figure', 'aspect' => '4-3'],
            ],
            [
                'type' => 'quote',
                'uuid' => (string) Str::uuid(),
                'layout' => '50',
                'data' => [
                    'quote' => $this->copy($site, 'Een goede demo laat direct zien wat het CMS kan.', 'A good demo immediately shows what the CMS can do.', 'Une bonne demo montre tout de suite ce que le CMS peut faire.'),
                    'author' => $site['company'],
                    'source' => $this->copy($site, 'Seeded klantverhaal', 'Seeded client story', 'Temoignage demo'),
                ],
                'settings' => ['style' => 'highlight'],
            ],
            [
                'type' => 'gallery',
                'uuid' => (string) Str::uuid(),
                'layout' => '100',
                'data' => [
                    'images' => [$galleryA, $galleryB],
                    'caption_notes' => $this->copy($site, "Workshop setting\nCMS detail", "Workshop setting\nCMS detail", "Atelier\nDetail CMS"),
                ],
                'settings' => ['layout' => 'grid'],
            ],
            [
                'type' => 'video',
                'uuid' => (string) Str::uuid(),
                'layout' => '50',
                'data' => [
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'caption' => $this->copy($site, 'Voorbeeldvideo', 'Example video', 'Video exemple'),
                ],
                'settings' => ['provider' => 'youtube'],
            ],
            [
                'type' => 'attachment',
                'uuid' => (string) Str::uuid(),
                'layout' => '50',
                'data' => [
                    'file' => $attachment,
                    'display_title' => $this->copy($site, 'Website checklist', 'Website checklist', 'Checklist site web'),
                    'button_label' => $this->copy($site, 'Download bestand', 'Download file', 'Telecharger le fichier'),
                    'description' => $this->copy($site, 'Publieke bijlage vanuit een contentblok.', 'Public attachment from a content block.', 'Piece jointe publique depuis un bloc.'),
                ],
                'settings' => ['open_in_new_tab' => false],
            ],
            [
                'type' => 'button',
                'uuid' => (string) Str::uuid(),
                'layout' => '100',
                'data' => [
                    'label' => $this->copy($site, 'Neem contact op', 'Get in touch', 'Nous contacter'),
                    'url' => '/contact',
                ],
                'settings' => ['style' => 'primary', 'alignment' => 'left', 'open_in_new_tab' => false],
            ],
        ];
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

    /**
     * @param  array<string, mixed>  $site
     */
    private function copy(array $site, string $nl, string $en, string $fr): string
    {
        return match ($site['locale']) {
            'nl' => $nl,
            'fr' => $fr,
            default => $en,
        };
    }

    /**
     * @param  array<string, mixed>  $site
     */
    private function formLabel(array $site, string $type): string
    {
        return match ($type) {
            'quote' => $this->copy($site, 'Offerteaanvraag', 'Quote request', 'Demande de devis'),
            'event' => $this->copy($site, 'Evenement aanmelden', 'Event registration', 'Inscription evenement'),
            'application' => $this->copy($site, 'Sollicitatieformulier', 'Application form', 'Formulaire de candidature'),
            default => $this->copy($site, 'Contactformulier', 'Contact form', 'Formulaire de contact'),
        };
    }

    /**
     * @param  array<string, mixed>  $site
     */
    private function formDescription(array $site, string $type): string
    {
        return match ($type) {
            'quote' => $this->copy($site, 'Vraag een voorstel aan voor uw project.', 'Request a proposal for your project.', 'Demandez une proposition pour votre projet.'),
            'event' => $this->copy($site, 'Meld u aan voor een training of demo dag.', 'Register for a training or demo day.', 'Inscrivez-vous a une formation ou journee demo.'),
            'application' => $this->copy($site, 'Reageer op een vacature met uw gegevens en bijlage.', 'Apply for a vacancy with your details and attachment.', 'Postulez avec vos coordonnees et piece jointe.'),
            default => $this->copy($site, 'Stel een vraag aan het team.', 'Ask the team a question.', 'Posez une question a l equipe.'),
        };
    }

    /**
     * @param  array<string, mixed>  $site
     */
    private function formSubmitText(array $site, string $type): string
    {
        return match ($type) {
            'quote' => $this->copy($site, 'Offerte aanvragen', 'Request quote', 'Demander un devis'),
            'event' => $this->copy($site, 'Aanmelden', 'Register', 'S inscrire'),
            'application' => $this->copy($site, 'Sollicitatie versturen', 'Send application', 'Envoyer la candidature'),
            default => $this->copy($site, 'Bericht versturen', 'Send message', 'Envoyer le message'),
        };
    }

    /**
     * @param  array<string, mixed>  $site
     */
    private function formSuccessMessage(array $site, string $type): string
    {
        return match ($type) {
            'quote' => $this->copy($site, 'Bedankt. We sturen snel een reactie.', 'Thanks. We will respond soon.', 'Merci. Nous repondrons rapidement.'),
            'event' => $this->copy($site, 'Bedankt, uw aanmelding is ontvangen.', 'Thanks, your registration has been received.', 'Merci, votre inscription a ete recue.'),
            'application' => $this->copy($site, 'Bedankt, uw sollicitatie is ontvangen.', 'Thanks, your application has been received.', 'Merci, votre candidature a ete recue.'),
            default => $this->copy($site, 'Bedankt, uw bericht is ontvangen.', 'Thanks, your message has been received.', 'Merci, votre message a ete recu.'),
        };
    }

    /**
     * @param  array<string, mixed>  $site
     * @return array<string, string>
     */
    private function selectOptions(array $site): array
    {
        return [
            'project' => $this->copy($site, 'Project', 'Project', 'Projet'),
            'support' => $this->copy($site, 'Support', 'Support', 'Support'),
            'training' => $this->copy($site, 'Training', 'Training', 'Formation'),
        ];
    }

    /**
     * @param  array<string, mixed>  $site
     */
    private function downloadName(array $site, string $type): string
    {
        if ($type === 'protected') {
            return $this->copy($site, 'Beveiligde projectbrief', 'Protected project brief', 'Brief projet protege');
        }

        return $this->copy($site, 'Publieke servicegids', 'Public service guide', 'Guide de service public');
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
