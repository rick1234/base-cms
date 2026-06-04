<?php

namespace Database\Seeders;

use App\Actions\Admin\Translations\SyncTranslationKeys;
use App\Models\Cms\TranslationKey;
use App\Support\Localization\TranslationRepository;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Seeder;

class TranslationModuleSeeder extends Seeder
{
    public function run(SyncTranslationKeys $syncTranslationKeys, TranslationRepository $translations): void
    {
        $syncTranslationKeys->handle();

        $sourceLocale = $translations->sourceLocale();
        $this->seedKnownTranslations($sourceLocale);
        $this->seedDutchValuesForAllKeys($sourceLocale);
        $this->removeLegacyAccessTerms();
        $this->removeObsoleteFlashTerms();
        $this->removeObsoleteContentLabelTerms();
        $this->removeObsoleteModuleManagerTerms();
        $this->removeObsoleteCountryShippingTerms();
        $this->removeObsoleteCountryPackageRefreshTerms();
        $this->removeObsoleteTechnicalEmptyStateTerms();
    }

    private function seedKnownTranslations(string $sourceLocale): void
    {
        foreach (self::defaults() as $key => $values) {
            $translationKeys = TranslationKey::query()
                ->whereIn('area', ['admin', 'frontend', 'shared'])
                ->where('group', '*')
                ->where('key', $key)
                ->get();

            if ($translationKeys->isEmpty()) {
                $translationKeys = new EloquentCollection([
                    TranslationKey::query()->create([
                        'area' => 'shared',
                        'group' => '*',
                        'key' => $key,
                        'source_locale' => $sourceLocale,
                        'source_text' => $values['en'],
                        'status' => 'active',
                        'is_system' => true,
                        'last_seen_at' => now(),
                    ]),
                ]);
            }

            $translationKeys->each(function (TranslationKey $translationKey) use ($sourceLocale, $values): void {
                $translationKey->forceFill([
                    'source_locale' => $sourceLocale,
                    'source_text' => $values['en'],
                    'status' => 'active',
                    'is_system' => true,
                    'last_seen_at' => now(),
                ])->save();

                foreach ($values as $locale => $value) {
                    $this->upsertValue($translationKey, (string) $locale, $value);
                }
            });
        }
    }

    private function seedDutchValuesForAllKeys(string $sourceLocale): void
    {
        $defaults = self::defaults();

        TranslationKey::query()
            ->where('group', '*')
            ->with('values')
            ->get()
            ->each(function (TranslationKey $translationKey) use ($defaults, $sourceLocale): void {
                $values = $defaults[$translationKey->key] ?? null;
                $sourceText = $values['en'] ?? ($translationKey->source_text ?: $translationKey->key);
                $dutchValue = $values['nl']
                    ?? $translationKey->valueFor('nl')?->value
                    ?? $sourceText;

                $translationKey->forceFill([
                    'source_locale' => $sourceLocale,
                    'source_text' => $sourceText,
                    'status' => $translationKey->status ?: 'active',
                ])->save();

                $this->upsertValue($translationKey, $sourceLocale, $sourceText);
                $this->upsertValue($translationKey, 'nl', (string) $dutchValue);
            });
    }

    private function upsertValue(TranslationKey $translationKey, string $locale, string $value): void
    {
        $translationKey->values()->updateOrCreate(
            ['locale' => $locale],
            [
                'value' => $value,
                'status' => 'active',
                'is_reviewed' => true,
                'reviewed_at' => now(),
            ],
        );
    }

    private function removeLegacyAccessTerms(): void
    {
        TranslationKey::query()
            ->whereIn('key', [
                'RBAC',
                'RBAC Log',
                'RBAC log overview',
                'View RBAC log entry',
            ])
            ->get()
            ->each(function (TranslationKey $translationKey): void {
                $translationKey->values()->delete();
                $translationKey->delete();
            });
    }

    private function removeObsoleteFlashTerms(): void
    {
        TranslationKey::query()
            ->whereIn('key', [
                'Domain updated.',
                'Event duplicated.',
                'Page updated.',
                'Record updated.',
                'Template updated.',
            ])
            ->get()
            ->each(function (TranslationKey $translationKey): void {
                $translationKey->values()->delete();
                $translationKey->delete();
            });
    }

    private function removeObsoleteContentLabelTerms(): void
    {
        TranslationKey::query()
            ->whereIn('key', [
                'Berichten overzicht',
                'Content Categories',
                'Content category',
                'Content images',
                'Content item',
                'Content item created.',
                'Content item deleted.',
                'Content item duplicated.',
                'Content item saved.',
                'Content overview',
                'Content slider',
                'Dit content item is nog niet aan categorieen gekoppeld.',
                'Edit content category',
                'Edit content item',
                'Legacy content category tree and editor.',
                'Legacy content item overview and editor.',
                'Pages, content categories, blocks, images, and attachments.',
                'Sla het content item eerst op voordat u afbeeldingen toevoegt.',
                'Sla het content item eerst op voordat u een slider koppelt.',
            ])
            ->get()
            ->each(function (TranslationKey $translationKey): void {
                $translationKey->values()->delete();
                $translationKey->delete();
            });
    }

    private function removeObsoleteModuleManagerTerms(): void
    {
        TranslationKey::query()
            ->whereIn('key', [
                'Edit module',
                'Edit module category',
                'Module Categories',
                'Module Manager',
                'Module category overview',
                'Module overview',
                'ModuleCategorie',
            ])
            ->get()
            ->each(function (TranslationKey $translationKey): void {
                $translationKey->values()->delete();
                $translationKey->delete();
            });
    }

    private function removeObsoleteCountryShippingTerms(): void
    {
        TranslationKey::query()
            ->whereIn('key', [
                'Bigbox',
                'Envelope',
                'Smallbox',
                'Verzendkosten',
            ])
            ->get()
            ->each(function (TranslationKey $translationKey): void {
                $translationKey->values()->delete();
                $translationKey->delete();
            });
    }

    private function removeObsoleteCountryPackageRefreshTerms(): void
    {
        TranslationKey::query()
            ->whereIn('key', [
                'Localization data refreshed: :countries countries and :languages languages.',
                'Refresh package data',
            ])
            ->get()
            ->each(function (TranslationKey $translationKey): void {
                $translationKey->values()->delete();
                $translationKey->delete();
            });
    }

    private function removeObsoleteTechnicalEmptyStateTerms(): void
    {
        TranslationKey::query()
            ->whereIn('key', [
                'Er zijn nog geen andere producten beschikbaar.',
                'Er zijn nog geen berichten ontvangen.',
                'Er zijn nog geen categorieen toegevoegd.',
                'Er zijn nog geen formulieren beschikbaar voeg eerst een formulier toe',
                'No domains have been configured yet.',
                'No navigation menus have been created yet.',
                'No pages have been created yet.',
                'No records have been migrated or created for this screen yet.',
                'No templates have been configured yet.',
                'No website languages enabled yet.',
            ])
            ->get()
            ->each(function (TranslationKey $translationKey): void {
                $translationKey->values()->delete();
                $translationKey->delete();
            });
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function defaults(): array
    {
        $defaults = [
            '{0} Geen items|{1} :count item|[2,*] :count items' => ['en' => '{0} No items|{1} :count item|[2,*] :count items', 'nl' => '{0} Geen items|{1} :count item|[2,*] :count items'],
            '{0} Geen sets|{1} :count set|[2,*] :count sets' => ['en' => '{0} No sets|{1} :count set|[2,*] :count sets', 'nl' => '{0} Geen sets|{1} :count set|[2,*] :count sets'],
            '301 redirects' => ['en' => '301 redirects', 'nl' => '301 verwijzingen'],
            'Aangepast op' => ['en' => 'Updated at', 'nl' => 'Aangepast op'],
            'Actief' => ['en' => 'Active', 'nl' => 'Actief'],
            'Action Codes' => ['en' => 'Action Codes', 'nl' => 'Actiecodes'],
            'Action code overview' => ['en' => 'Action code overview', 'nl' => 'Actiecodes overzicht'],
            'Active' => ['en' => 'Active', 'nl' => 'Actief'],
            'Add' => ['en' => 'Add', 'nl' => 'Toevoegen'],
            'Admin Modules' => ['en' => 'Admin Modules', 'nl' => 'Admin modules'],
            'Afronden binnen :count minuten' => ['en' => 'Finish within :count minutes', 'nl' => 'Afronden binnen :count minuten'],
            'Afronden binnen :count minuut' => ['en' => 'Finish within :count minute', 'nl' => 'Afronden binnen :count minuut'],
            'Afbeeldingen opslaan' => ['en' => 'Saving images', 'nl' => 'Afbeeldingen opslaan'],
            'Alle categorieen' => ['en' => 'All categories', 'nl' => 'Alle categorieen'],
            'Aantal' => ['en' => 'Amount', 'nl' => 'Aantal'],
            'Alle' => ['en' => 'All', 'nl' => 'Alle'],
            'Annuleren' => ['en' => 'Cancel', 'nl' => 'Annuleren'],
            'Archived' => ['en' => 'Archived', 'nl' => 'Gearchiveerd'],
            'Area' => ['en' => 'Area', 'nl' => 'Gebied'],
            'Auteur' => ['en' => 'Author', 'nl' => 'Auteur'],
            'Backend' => ['en' => 'Backend', 'nl' => 'Backend'],
            'Backend toegang' => ['en' => 'Backend access', 'nl' => 'Backend toegang'],
            'Banner' => ['en' => 'Banner', 'nl' => 'Banner'],
            'Banner Categories' => ['en' => 'Banner Categories', 'nl' => 'Banner categorieen'],
            'Banner images, categories, and translations.' => ['en' => 'Banner images, categories, and translations.', 'nl' => 'Banner afbeeldingen, categorieen en vertalingen.'],
            'Banner category overview' => ['en' => 'Banner category overview', 'nl' => 'Banner categorieen overzicht'],
            'Banner overview' => ['en' => 'Banner overview', 'nl' => 'Banners overzicht'],
            'Banners' => ['en' => 'Banners', 'nl' => 'Banners'],
            'Banners overview' => ['en' => 'Banners overview', 'nl' => 'Banners overzicht'],
            'Bekijken' => ['en' => 'View', 'nl' => 'Bekijken'],
            'Bestanden voorbereiden' => ['en' => 'Preparing files', 'nl' => 'Bestanden voorbereiden'],
            'Breadcrumbs' => ['en' => 'Breadcrumbs', 'nl' => 'Broodkruimels'],
            'Bestandsgrootte' => ['en' => 'File size', 'nl' => 'Bestandsgrootte'],
            'Bestandstypes' => ['en' => 'File types', 'nl' => 'Bestandstypes'],
            'Bekijk gekoppelde items' => ['en' => 'View linked items', 'nl' => 'Bekijk gekoppelde items'],
            'Bewerk: :module' => ['en' => 'Edit: :module', 'nl' => 'Bewerk: :module'],
            'Bewerk: :name' => ['en' => 'Edit: :name', 'nl' => 'Bewerk: :name'],
            'Bewerk: :title' => ['en' => 'Edit: :title', 'nl' => 'Bewerk: :title'],
            'Bewerken' => ['en' => 'Edit', 'nl' => 'Bewerken'],
            'Bijlagen' => ['en' => 'Attachments', 'nl' => 'Bijlagen'],
            'Blokken' => ['en' => 'Blocks', 'nl' => 'Blokken'],
            'Body' => ['en' => 'Body', 'nl' => 'Tekst'],
            'Bulk banner uploader' => ['en' => 'Bulk banner uploader', 'nl' => 'Bulk banner uploader'],
            'Cancel' => ['en' => 'Cancel', 'nl' => 'Annuleren'],
            'Catalog' => ['en' => 'Catalog', 'nl' => 'Catalogus'],
            'Catalog Brands' => ['en' => 'Catalog Brands', 'nl' => 'Merken'],
            'Catalog Categories' => ['en' => 'Catalog Categories', 'nl' => 'Catalogus categorieen'],
            'Catalog Products' => ['en' => 'Catalog Products', 'nl' => 'Catalogus'],
            'Catalog Promotions' => ['en' => 'Catalog Promotions', 'nl' => 'Promoties'],
            'Catalog Reviews' => ['en' => 'Catalog Reviews', 'nl' => 'Reviews'],
            'Catalog product options, combinations, translations, images, videos, attachments, and stock.' => ['en' => 'Catalog product options, combinations, translations, images, videos, attachments, and stock.', 'nl' => 'Catalogus productopties, combinaties, vertalingen, afbeeldingen, videos, bijlagen en voorraad.'],
            'Catalog brand overview' => ['en' => 'Catalog brand overview', 'nl' => 'Merken overzicht'],
            'Catalog category overview' => ['en' => 'Catalog category overview', 'nl' => 'Categorieen overzicht'],
            'Catalog product overview' => ['en' => 'Catalog product overview', 'nl' => 'Artikelen overzicht'],
            'Catalog promotion overview' => ['en' => 'Catalog promotion overview', 'nl' => 'Promoties overzicht'],
            'Catalog review overview' => ['en' => 'Catalog review overview', 'nl' => 'Reviews overzicht'],
            'Catalogus overzicht' => ['en' => 'Catalog overview', 'nl' => 'Catalogus overzicht'],
            'Categorie' => ['en' => 'Category', 'nl' => 'Categorie'],
            'Categorie selecteren' => ['en' => 'Select category', 'nl' => 'Categorie selecteren'],
            'Categorie wissen' => ['en' => 'Clear category', 'nl' => 'Categorie wissen'],
            'Categorie volgorde opgeslagen.' => ['en' => 'Category order saved.', 'nl' => 'Categorie volgorde opgeslagen.'],
            'Categorieen kunnen alleen binnen hetzelfde niveau worden gesorteerd.' => ['en' => 'Categories can only be sorted within the same level.', 'nl' => 'Categorieen kunnen alleen binnen hetzelfde niveau worden gesorteerd.'],
            'Categorieen' => ['en' => 'Categories', 'nl' => 'Categorieen'],
            'Categorieen overzicht' => ['en' => 'Category overview', 'nl' => 'Categorieen overzicht'],
            'Category slider' => ['en' => 'Category slider', 'nl' => 'Categorie slider'],
            'Choose an option' => ['en' => 'Choose an option', 'nl' => 'Kies een optie'],
            'Commerce' => ['en' => 'Commerce', 'nl' => 'Webshop'],
            'Configuration' => ['en' => 'Configuration', 'nl' => 'Configuratie'],
            'Content' => ['en' => 'Content', 'nl' => 'Inhoud'],
            'Content blocks' => ['en' => 'Content blocks', 'nl' => 'Contentblokken'],
            'Content blocks saved.' => ['en' => 'Content blocks saved.', 'nl' => 'Contentblokken opgeslagen.'],
            'ContentItem' => ['en' => 'Page', 'nl' => 'Pagina'],
            'Add block' => ['en' => 'Add block', 'nl' => 'Blok toevoegen'],
            'Alignment' => ['en' => 'Alignment', 'nl' => 'Uitlijning'],
            'Alt text' => ['en' => 'Alt text', 'nl' => 'Alt-tekst'],
            'Anchor ID' => ['en' => 'Anchor ID', 'nl' => 'Anker ID'],
            'Aspect ratio' => ['en' => 'Aspect ratio', 'nl' => 'Beeldverhouding'],
            'Attachment' => ['en' => 'Attachment', 'nl' => 'Bijlage'],
            'Author' => ['en' => 'Author', 'nl' => 'Auteur'],
            'Auto detect' => ['en' => 'Auto detect', 'nl' => 'Automatisch herkennen'],
            'Background' => ['en' => 'Background', 'nl' => 'Achtergrond'],
            'Block width' => ['en' => 'Block width', 'nl' => 'Blokbreedte'],
            'Button' => ['en' => 'Button', 'nl' => 'Knop'],
            'Button label' => ['en' => 'Button label', 'nl' => 'Knoptekst'],
            'Caption' => ['en' => 'Caption', 'nl' => 'Bijschrift'],
            'Caption notes' => ['en' => 'Caption notes', 'nl' => 'Bijschrift-notities'],
            'Carousel ready' => ['en' => 'Carousel ready', 'nl' => 'Carousel voorbereid'],
            'Center' => ['en' => 'Center', 'nl' => 'Midden'],
            'Change language' => ['en' => 'Change language', 'nl' => 'Taal wijzigen', 'fr' => 'Changer de langue'],
            'Choose language' => ['en' => 'Choose language', 'nl' => 'Kies taal', 'fr' => 'Choisir la langue'],
            'Choose a width from 0 to 100 percent. Blocks form a grid when their widths fit next to each other.' => ['en' => 'Choose a width from 0 to 100 percent. Blocks form a grid when their widths fit next to each other.', 'nl' => 'Kies een breedte van 0 tot 100 procent. Blokken vormen een grid wanneer hun breedtes naast elkaar passen.'],
            'Close' => ['en' => 'Close', 'nl' => 'Sluiten', 'fr' => 'Fermer'],
            'Collapse all' => ['en' => 'Collapse all', 'nl' => 'Alles inklappen'],
            'Current language' => ['en' => 'Current language', 'nl' => 'Huidige taal', 'fr' => 'Langue actuelle'],
            'Default' => ['en' => 'Default', 'nl' => 'Standaard'],
            'Delete block' => ['en' => 'Delete block', 'nl' => 'Blok verwijderen'],
            'Display title' => ['en' => 'Display title', 'nl' => 'Weergavetitel'],
            'Drag block' => ['en' => 'Drag block', 'nl' => 'Blok slepen'],
            'Duplicate' => ['en' => 'Duplicate', 'nl' => 'Dupliceren'],
            'Edit block' => ['en' => 'Edit block', 'nl' => 'Blok bewerken'],
            'Edit block content' => ['en' => 'Edit block content', 'nl' => 'Blokinhoud bewerken'],
            'Empty button block' => ['en' => 'Empty button block', 'nl' => 'Leeg knopblok'],
            'Empty quote block' => ['en' => 'Empty quote block', 'nl' => 'Leeg citaatblok'],
            'Empty text block' => ['en' => 'Empty text block', 'nl' => 'Leeg tekstblok'],
            'Expand all' => ['en' => 'Expand all', 'nl' => 'Alles uitklappen'],
            'Figure' => ['en' => 'Figure', 'nl' => 'Figuur'],
            'Full width' => ['en' => 'Full width', 'nl' => 'Volledige breedte'],
            'Gallery' => ['en' => 'Gallery', 'nl' => 'Galerij'],
            'Gallery layout' => ['en' => 'Gallery layout', 'nl' => 'Galerij-layout'],
            'Grid' => ['en' => 'Grid', 'nl' => 'Raster'],
            'Half' => ['en' => 'Half', 'nl' => 'Half'],
            'Highlight' => ['en' => 'Highlight', 'nl' => 'Uitgelicht'],
            'Image layout' => ['en' => 'Image layout', 'nl' => 'Afbeeldingslayout'],
            'Insert block' => ['en' => 'Insert block', 'nl' => 'Blok invoegen'],
            'Intro style' => ['en' => 'Intro style', 'nl' => 'Intro-opmaak'],
            'Label' => ['en' => 'Label', 'nl' => 'Label'],
            'Left' => ['en' => 'Left', 'nl' => 'Links'],
            'Level' => ['en' => 'Level', 'nl' => 'Kopniveau'],
            'Link URL' => ['en' => 'Link URL', 'nl' => 'Link-url'],
            'Masonry' => ['en' => 'Masonry', 'nl' => 'Masonry'],
            'Minimal' => ['en' => 'Minimal', 'nl' => 'Minimaal'],
            'Move down' => ['en' => 'Move down', 'nl' => 'Omlaag verplaatsen'],
            'Move up' => ['en' => 'Move up', 'nl' => 'Omhoog verplaatsen'],
            'Muted' => ['en' => 'Muted', 'nl' => 'Gedempt'],
            'No attachment selected' => ['en' => 'No attachment selected', 'nl' => 'Geen bijlage geselecteerd'],
            'No gallery images selected' => ['en' => 'No gallery images selected', 'nl' => 'Geen galerijafbeeldingen geselecteerd'],
            'No image selected' => ['en' => 'No image selected', 'nl' => 'Geen afbeelding geselecteerd'],
            'No video URL set' => ['en' => 'No video URL set', 'nl' => 'Geen video-url ingesteld'],
            'None' => ['en' => 'None', 'nl' => 'Geen'],
            'One quarter' => ['en' => 'One quarter', 'nl' => 'Een kwart'],
            'One third' => ['en' => 'One third', 'nl' => 'Een derde'],
            'Open in new tab' => ['en' => 'Open in new tab', 'nl' => 'Openen in nieuw tabblad'],
            'Optional anchor without #.' => ['en' => 'Optional anchor without #.', 'nl' => 'Optioneel anker zonder #.'],
            'Optional captions, one per line in image order.' => ['en' => 'Optional captions, one per line in image order.', 'nl' => 'Optionele bijschriften, een per regel in de volgorde van de afbeeldingen.'],
            'Original' => ['en' => 'Original', 'nl' => 'Origineel'],
            'Poster image' => ['en' => 'Poster image', 'nl' => 'Posterafbeelding'],
            'Primary' => ['en' => 'Primary', 'nl' => 'Primair'],
            'Provider' => ['en' => 'Provider', 'nl' => 'Provider'],
            'Quote' => ['en' => 'Quote', 'nl' => 'Citaat'],
            'Right' => ['en' => 'Right', 'nl' => 'Rechts'],
            'Save blocks' => ['en' => 'Save blocks', 'nl' => 'Blokken opslaan'],
            'Save changes' => ['en' => 'Save changes', 'nl' => 'Wijzigingen opslaan'],
            'Samenvatting van inzending' => ['en' => 'Submission summary', 'nl' => 'Samenvatting van inzending'],
            'Saving...' => ['en' => 'Saving...', 'nl' => 'Opslaan...'],
            'Secondary' => ['en' => 'Secondary', 'nl' => 'Secundair'],
            'Style' => ['en' => 'Style', 'nl' => 'Stijl'],
            'Text' => ['en' => 'Text', 'nl' => 'Tekst'],
            'Two thirds' => ['en' => 'Two thirds', 'nl' => 'Twee derde'],
            'Untitled title block' => ['en' => 'Untitled title block', 'nl' => 'Titelloos titelblok'],
            'Video URL' => ['en' => 'Video URL', 'nl' => 'Video-url'],
            'Wide' => ['en' => 'Wide', 'nl' => 'Breed'],
            'Countries' => ['en' => 'Countries', 'nl' => 'Landen'],
            'Country overview' => ['en' => 'Country overview', 'nl' => 'Landen overzicht'],
            'Dashboard' => ['en' => 'Dashboard', 'nl' => 'Dashboard'],
            'Date Range' => ['en' => 'Date Range', 'nl' => 'Datumperiode'],
            'Default website language' => ['en' => 'Default website language', 'nl' => 'Standaard websitettaal'],
            'Definition' => ['en' => 'Definition', 'nl' => 'Definitie'],
            'Delivery Dates' => ['en' => 'Delivery Dates', 'nl' => 'Afleverdata'],
            'Delivery date overview' => ['en' => 'Delivery date overview', 'nl' => 'Afleverdata overzicht'],
            'Delete' => ['en' => 'Delete', 'nl' => 'Verwijderen'],
            'Delete record' => ['en' => 'Delete record', 'nl' => 'Record verwijderen'],
            'De afbeeldingen worden gecontroleerd. Dit kan even duren bij een grote batch.' => ['en' => 'The images are being checked. This can take a little while for a large batch.', 'nl' => 'De afbeeldingen worden gecontroleerd. Dit kan even duren bij een grote batch.'],
            'De bestanden worden opgeslagen en aan het fotoalbum toegevoegd.' => ['en' => 'The files are being saved and added to the photo album.', 'nl' => 'De bestanden worden opgeslagen en aan het fotoalbum toegevoegd.'],
            'Deze vertaling verwijderen' => ['en' => 'Delete this translation', 'nl' => 'Deze vertaling verwijderen'],
            'Description' => ['en' => 'Description', 'nl' => 'Omschrijving'],
            'Domain overview' => ['en' => 'Domain overview', 'nl' => 'Domein overzicht'],
            'Domains' => ['en' => 'Domains', 'nl' => 'Domeinen'],
            'Download Categories' => ['en' => 'Download Categories', 'nl' => 'Download categorieen'],
            'Download category overview' => ['en' => 'Download category overview', 'nl' => 'Download categorieen overzicht'],
            'Download overview' => ['en' => 'Download overview', 'nl' => 'Downloads overzicht'],
            'Downloads' => ['en' => 'Downloads', 'nl' => 'Downloads'],
            'Dutch' => ['en' => 'Dutch', 'nl' => 'Nederlands'],
            'Edit' => ['en' => 'Edit', 'nl' => 'Bewerken'],
            'Edit FAQ category' => ['en' => 'Edit FAQ category', 'nl' => 'FAQ categorie bewerken'],
            'Edit FAQ item' => ['en' => 'Edit FAQ item', 'nl' => 'FAQ item bewerken'],
            'Edit action code' => ['en' => 'Edit action code', 'nl' => 'Actiecode bewerken'],
            'Edit banner' => ['en' => 'Edit banner', 'nl' => 'Banner bewerken'],
            'Edit banner category' => ['en' => 'Edit banner category', 'nl' => 'Banner categorie bewerken'],
            'Edit catalog brand' => ['en' => 'Edit catalog brand', 'nl' => 'Merk bewerken'],
            'Edit catalog category' => ['en' => 'Edit catalog category', 'nl' => 'Catalogus categorie bewerken'],
            'Edit catalog product' => ['en' => 'Edit catalog product', 'nl' => 'Artikel bewerken'],
            'Edit catalog promotion' => ['en' => 'Edit catalog promotion', 'nl' => 'Promotie bewerken'],
            'Edit catalog review' => ['en' => 'Edit catalog review', 'nl' => 'Review bewerken'],
            'Edit country' => ['en' => 'Edit country', 'nl' => 'Land bewerken'],
            'Edit delivery date' => ['en' => 'Edit delivery date', 'nl' => 'Afleverdatum bewerken'],
            'Edit domain' => ['en' => 'Edit domain', 'nl' => 'Domein bewerken'],
            'Edit download' => ['en' => 'Edit download', 'nl' => 'Download bewerken'],
            'Edit download category' => ['en' => 'Edit download category', 'nl' => 'Download categorie bewerken'],
            'Edit event' => ['en' => 'Edit event', 'nl' => 'Evenement bewerken'],
            'Edit event category' => ['en' => 'Edit event category', 'nl' => 'Evenement categorie bewerken'],
            'Edit form' => ['en' => 'Edit form', 'nl' => 'Formulier bewerken'],
            'Edit form category' => ['en' => 'Edit form category', 'nl' => 'Formulier categorie bewerken'],
            'Edit language' => ['en' => 'Edit language', 'nl' => 'Taal bewerken'],
            'Edit location' => ['en' => 'Edit location', 'nl' => 'Vestiging bewerken'],
            'Edit location category' => ['en' => 'Edit location category', 'nl' => 'Vestiging categorie bewerken'],
            'Edit order' => ['en' => 'Edit order', 'nl' => 'Order bewerken'],
            'Edit redirect' => ['en' => 'Edit redirect', 'nl' => 'Redirect bewerken'],
            'Edit role' => ['en' => 'Edit role', 'nl' => 'Rol bewerken'],
            'Edit slider' => ['en' => 'Edit slider', 'nl' => 'Slider bewerken'],
            'Edit slider category' => ['en' => 'Edit slider category', 'nl' => 'Slider categorie bewerken'],
            'Edit translation' => ['en' => 'Edit translation', 'nl' => 'Vertaling bewerken'],
            'Edit user' => ['en' => 'Edit user', 'nl' => 'Gebruiker bewerken'],
            'Edit user category' => ['en' => 'Edit user category', 'nl' => 'Gebruiker categorie bewerken'],
            'Edit vacancy' => ['en' => 'Edit vacancy', 'nl' => 'Vacature bewerken'],
            'Edit vacancy category' => ['en' => 'Edit vacancy category', 'nl' => 'Vacature categorie bewerken'],
            'English' => ['en' => 'English', 'nl' => 'Engels'],
            'Event Categories' => ['en' => 'Event Categories', 'nl' => 'Evenement categorieen'],
            'Event category overview' => ['en' => 'Event category overview', 'nl' => 'Evenement categorieen overzicht'],
            'Events' => ['en' => 'Events', 'nl' => 'Evenementen'],
            'Events, event categories, parts, images, and attachments.' => ['en' => 'Events, event categories, parts, images, and attachments.', 'nl' => 'Evenementen, categorieen, onderdelen, afbeeldingen en bijlagen.'],
            'Events overview' => ['en' => 'Events overview', 'nl' => 'Evenementen overzicht'],
            'Er zijn nog geen categorieen toegevoegd.' => ['en' => 'No categories have been added yet.', 'nl' => 'Er zijn nog geen categorieen toegevoegd.'],
            'First login' => ['en' => 'First login', 'nl' => 'Eerste login'],
            'FAQ' => ['en' => 'FAQ', 'nl' => 'FAQ'],
            'FAQ Categories' => ['en' => 'FAQ Categories', 'nl' => 'FAQ categorieen'],
            'FAQ items' => ['en' => 'FAQ items', 'nl' => 'FAQ items'],
            'FAQ category overview' => ['en' => 'FAQ category overview', 'nl' => 'FAQ categorieen overzicht'],
            'FAQ overview' => ['en' => 'FAQ overview', 'nl' => 'FAQ overzicht'],
            'Fallback' => ['en' => 'Fallback', 'nl' => 'Fallback'],
            'Faq' => ['en' => 'FAQ', 'nl' => 'FAQ'],
            'FaqCategorie' => ['en' => 'FAQ category', 'nl' => 'FAQ categorie'],
            'File' => ['en' => 'File', 'nl' => 'Bestand'],
            'Bevestigingsmail opslaan' => ['en' => 'Save confirmation email', 'nl' => 'Bevestigingsmail opslaan'],
            'Form Categories' => ['en' => 'Form Categories', 'nl' => 'Formulier categorieen'],
            'Form builder blocks, rows, fields, options, messages, and submissions.' => ['en' => 'Form builder blocks, rows, fields, options, messages, and submissions.', 'nl' => 'Form builder blokken, rijen, velden, opties, berichten en inzendingen.'],
            'Form category overview' => ['en' => 'Form category overview', 'nl' => 'Formulier categorieen overzicht'],
            'Form messages' => ['en' => 'Form messages', 'nl' => 'Formulier berichten'],
            'Formulier opgeslagen' => ['en' => 'Form saved.', 'nl' => 'Formulier opgeslagen'],
            'Formulier opslaan' => ['en' => 'Save form', 'nl' => 'Formulier opslaan'],
            'Formuliernaam' => ['en' => 'Form name', 'nl' => 'Formuliernaam'],
            'Formuliervelden' => ['en' => 'Form fields', 'nl' => 'Formuliervelden'],
            'Forms' => ['en' => 'Forms', 'nl' => 'Formulieren'],
            'Forms overview' => ['en' => 'Forms overview', 'nl' => 'Formulieren overzicht'],
            'Forgotten password' => ['en' => 'Forgotten password', 'nl' => 'Wachtwoord vergeten'],
            'Frontend' => ['en' => 'Frontend', 'nl' => 'Frontend'],
            'French' => ['en' => 'French', 'nl' => 'Frans'],
            'Gemaakt op' => ['en' => 'Created at', 'nl' => 'Gemaakt op'],
            'Geen blokken' => ['en' => 'No blocks', 'nl' => 'Geen blokken'],
            'Geen gekoppelde items gevonden.' => ['en' => 'No linked items found.', 'nl' => 'Geen gekoppelde items gevonden.'],
            'Geen items in deze set.' => ['en' => 'No items in this set.', 'nl' => 'Geen items in deze set.'],
            'Geen rollen gevonden.' => ['en' => 'No roles found.', 'nl' => 'Geen rollen gevonden.'],
            'Geen tijdschema sets gevonden.' => ['en' => 'No schedule sets found.', 'nl' => 'Geen tijdschema sets gevonden.'],
            'Geen translations gevonden.' => ['en' => 'No translations found.', 'nl' => 'Geen vertalingen gevonden.'],
            'Gebruikers' => ['en' => 'Users', 'nl' => 'Gebruikers'],
            'Gebruikersnaam' => ['en' => 'Username', 'nl' => 'Gebruikersnaam'],
            'Generate address sticker' => ['en' => 'Generate address sticker', 'nl' => 'Adressticker maken'],
            'Generate order export' => ['en' => 'Generate order export', 'nl' => 'Order export maken'],
            'German' => ['en' => 'German', 'nl' => 'Duits'],
            'Group' => ['en' => 'Group', 'nl' => 'Groep'],
            'Guestbook' => ['en' => 'Guestbook', 'nl' => 'Gastenboek'],
            'Home' => ['en' => 'Home', 'nl' => 'Home'],
            'Hoofdcategorie toevoegen' => ['en' => 'Add root category', 'nl' => 'Hoofdcategorie toevoegen'],
            'Image' => ['en' => 'Image', 'nl' => 'Afbeelding'],
            'Image deleted.' => ['en' => 'Image deleted.', 'nl' => 'Afbeelding verwijderd.'],
            'Image order saved.' => ['en' => 'Image order saved.', 'nl' => 'Afbeeldingsvolgorde opgeslagen.'],
            'Image SEO options saved.' => ['en' => 'Image SEO options saved.', 'nl' => 'Afbeelding SEO opties opgeslagen.'],
            'Image uploaded.' => ['en' => 'Image uploaded.', 'nl' => 'Afbeelding geupload.'],
            'Images' => ['en' => 'Images', 'nl' => 'Afbeeldingen'],
            'Images uploaded.' => ['en' => 'Images uploaded.', 'nl' => 'Afbeeldingen geupload.'],
            'Inactief' => ['en' => 'Inactive', 'nl' => 'Inactief'],
            'Ingelogd als: ' => ['en' => 'Logged in as: ', 'nl' => 'Ingelogd als: '],
            'Inklappen' => ['en' => 'Collapse', 'nl' => 'Inklappen'],
            'Item toevoegen' => ['en' => 'Add item', 'nl' => 'Item toevoegen'],
            'Item verwijderen' => ['en' => 'Remove item', 'nl' => 'Item verwijderen'],
            'Ja' => ['en' => 'Yes', 'nl' => 'Ja'],
            'JPG, PNG, GIF en WebP' => ['en' => 'JPG, PNG, GIF, and WebP', 'nl' => 'JPG, PNG, GIF en WebP'],
            'Key' => ['en' => 'Key', 'nl' => 'Sleutel'],
            'Kies een optie' => ['en' => 'Choose an option', 'nl' => 'Kies een optie'],
            'Language' => ['en' => 'Language', 'nl' => 'Taal', 'fr' => 'Langue'],
            'Localization' => ['en' => 'Localization', 'nl' => 'Vertalingen'],
            'Locations' => ['en' => 'Locations', 'nl' => 'Vestigingen'],
            'Location Categories' => ['en' => 'Location Categories', 'nl' => 'Vestiging categorieen'],
            'Location category overview' => ['en' => 'Location category overview', 'nl' => 'Vestiging categorieen overzicht'],
            'Location images' => ['en' => 'Location images', 'nl' => 'Vestiging afbeeldingen'],
            'Location opening hours' => ['en' => 'Location opening hours', 'nl' => 'Vestiging openingstijden'],
            'Location overview' => ['en' => 'Location overview', 'nl' => 'Vestigingen overzicht'],
            'Media' => ['en' => 'Media', 'nl' => 'Media'],
            'Maximaal :count afbeeldingen per upload' => ['en' => 'Maximum :count images per upload', 'nl' => 'Maximaal :count afbeeldingen per upload'],
            'Maximaal :count afbeelding per upload' => ['en' => 'Maximum :count image per upload', 'nl' => 'Maximaal :count afbeelding per upload'],
            'Maximaal :size per afbeelding' => ['en' => 'Maximum :size per image', 'nl' => 'Maximaal :size per afbeelding'],
            'Meerdere afbeeldingen tegelijk uploaden.' => ['en' => 'Upload multiple images at once.', 'nl' => 'Meerdere afbeeldingen tegelijk uploaden.'],
            'Metadata' => ['en' => 'Metadata', 'nl' => 'Metadata'],
            'Meta Description' => ['en' => 'Meta Description', 'nl' => 'Meta omschrijving'],
            'Module' => ['en' => 'Module', 'nl' => 'Module'],
            'Module rechten' => ['en' => 'Module permissions', 'nl' => 'Module rechten'],
            'Modules' => ['en' => 'Modules', 'nl' => 'Modules'],
            'Name' => ['en' => 'Name', 'nl' => 'Naam'],
            'Naam' => ['en' => 'Name', 'nl' => 'Naam'],
            'Naamloos item' => ['en' => 'Untitled item', 'nl' => 'Naamloos item'],
            'Nee' => ['en' => 'No', 'nl' => 'Nee'],
            'Nieuwe set' => ['en' => 'New set', 'nl' => 'Nieuwe set'],
            'Nog :count extra gekoppelde items.' => ['en' => ':count more linked items.', 'nl' => 'Nog :count extra gekoppelde items.'],
            'No translations found.' => ['en' => 'No translations found.', 'nl' => 'Geen vertalingen gevonden.'],
            'Offline' => ['en' => 'Offline', 'nl' => 'Offline'],
            'Omschrijving' => ['en' => 'Description', 'nl' => 'Omschrijving'],
            'Online' => ['en' => 'Online', 'nl' => 'Online'],
            'Only missing' => ['en' => 'Only missing', 'nl' => 'Alleen ontbrekende'],
            'Ook onderliggende' => ['en' => 'Include child categories', 'nl' => 'Ook onderliggende'],
            'Opslaan' => ['en' => 'Save', 'nl' => 'Opslaan'],
            'Opslaan en blijven' => ['en' => 'Save and stay', 'nl' => 'Opslaan en blijven'],
            'Opties' => ['en' => 'Options', 'nl' => 'Opties'],
            'Options' => ['en' => 'Options', 'nl' => 'Opties'],
            'Order action' => ['en' => 'Order action', 'nl' => 'Order actie'],
            'Order export overview' => ['en' => 'Order export overview', 'nl' => 'Order export overzicht'],
            'Order item list' => ['en' => 'Order item list', 'nl' => 'Orderregels'],
            'Order overview' => ['en' => 'Order overview', 'nl' => 'Orders overzicht'],
            'Order totals' => ['en' => 'Order totals', 'nl' => 'Order totalen'],
            'Orders' => ['en' => 'Orders', 'nl' => 'Orders'],
            'Page' => ['en' => 'Page', 'nl' => 'Pagina'],
            'Page Categories' => ['en' => 'Page Categories', 'nl' => 'Paginacategorieen'],
            'Page category' => ['en' => 'Page category', 'nl' => 'Paginacategorie'],
            'Page category overview' => ['en' => 'Page category overview', 'nl' => 'Paginacategorieen overzicht'],
            'Page images' => ['en' => 'Page images', 'nl' => 'Pagina afbeeldingen'],
            'Page slider' => ['en' => 'Page slider', 'nl' => 'Pagina slider'],
            'Pages' => ['en' => 'Pages', 'nl' => 'Pagina\'s'],
            'Pages overview' => ['en' => 'Pages overview', 'nl' => 'Pagina\'s overzicht'],
            'Pages, page categories, blocks, images, and attachments.' => ['en' => 'Pages, page categories, blocks, images, and attachments.', 'nl' => 'Pagina\'s, paginacategorieen, blokken, afbeeldingen en bijlagen.'],
            'Payment Methods' => ['en' => 'Payment Methods', 'nl' => 'Betaalmethoden'],
            'Payment method overview' => ['en' => 'Payment method overview', 'nl' => 'Betaalmethoden overzicht'],
            'Platform' => ['en' => 'Platform', 'nl' => 'Platform'],
            'Pricing' => ['en' => 'Pricing', 'nl' => 'Prijzen'],
            'Product combinations' => ['en' => 'Product combinations', 'nl' => 'Productcombinaties'],
            'Product images' => ['en' => 'Product images', 'nl' => 'Product afbeeldingen'],
            'Product options' => ['en' => 'Product options', 'nl' => 'Product opties'],
            'Product stock' => ['en' => 'Product stock', 'nl' => 'Product voorraad'],
            'Product translations' => ['en' => 'Product translations', 'nl' => 'Product vertalingen'],
            'Product videos' => ['en' => 'Product videos', 'nl' => 'Product videos'],
            'Products' => ['en' => 'Products', 'nl' => 'Producten'],
            'Products, categories, brands, promotions, discounts, media, stock, reviews, and product options.' => ['en' => 'Products, categories, brands, promotions, discounts, media, stock, reviews, and product options.', 'nl' => 'Producten, categorieen, merken, promoties, kortingen, media, voorraad, reviews en product opties.'],
            'Promotion' => ['en' => 'Promotion', 'nl' => 'Promotie'],
            'Querystring' => ['en' => 'Querystring', 'nl' => 'Querystring'],
            'Record' => ['en' => 'Record', 'nl' => 'Record'],
            'Record created.' => ['en' => 'Record created.', 'nl' => 'Record aangemaakt.'],
            'Record deleted.' => ['en' => 'Record deleted.', 'nl' => 'Record verwijderd.'],
            'Record saved.' => ['en' => 'Record saved.', 'nl' => 'Record opgeslagen.'],
            'Redirect overview' => ['en' => 'Redirect overview', 'nl' => 'Redirects overzicht'],
            'Redirects' => ['en' => 'Redirects', 'nl' => 'Redirects'],
            'Reset password' => ['en' => 'Reset password', 'nl' => 'Wachtwoord resetten'],
            'Reset product sort order' => ['en' => 'Reset product sort order', 'nl' => 'Product sortering herstellen'],
            'Reset user password' => ['en' => 'Reset user password', 'nl' => 'Gebruiker wachtwoord resetten'],
            'Rechten' => ['en' => 'Permissions', 'nl' => 'Rechten'],
            'Reviewed' => ['en' => 'Reviewed', 'nl' => 'Gecontroleerd'],
            'Role overview' => ['en' => 'Role overview', 'nl' => 'Rollen overzicht'],
            'Roles and Permissions' => ['en' => 'Roles and Permissions', 'nl' => 'Rollen en rechten'],
            'Rol' => ['en' => 'Role', 'nl' => 'Rol'],
            'SEO' => ['en' => 'SEO', 'nl' => 'SEO'],
            'Save' => ['en' => 'Save', 'nl' => 'Opslaan'],
            'Saved' => ['en' => 'Saved', 'nl' => 'Opgeslagen'],
            'Search' => ['en' => 'Search', 'nl' => 'Zoeken'],
            'Security' => ['en' => 'Security', 'nl' => 'Beveiliging'],
            'Selecteer' => ['en' => 'Select', 'nl' => 'Selecteer'],
            'Selecteer een categorie om de URL en gekoppelde items te bekijken.' => ['en' => 'Select a category to view the URL and linked items.', 'nl' => 'Selecteer een categorie om de URL en gekoppelde items te bekijken.'],
            'Set naam' => ['en' => 'Set name', 'nl' => 'Set naam'],
            'Set toevoegen' => ['en' => 'Add set', 'nl' => 'Set toevoegen'],
            'Set verwijderen' => ['en' => 'Remove set', 'nl' => 'Set verwijderen'],
            'Shared' => ['en' => 'Shared', 'nl' => 'Gedeeld'],
            'Settings' => ['en' => 'Settings', 'nl' => 'Instellingen'],
            'Slider Categories' => ['en' => 'Slider Categories', 'nl' => 'Slider categorieen'],
            'Slider category overview' => ['en' => 'Slider category overview', 'nl' => 'Slider categorieen overzicht'],
            'Slider overview' => ['en' => 'Slider overview', 'nl' => 'Sliders overzicht'],
            'Sliders' => ['en' => 'Sliders', 'nl' => 'Sliders'],
            'Sla het evenement eerst op voordat u het tijdschema opbouwt.' => ['en' => 'Save the event before building the schedule.', 'nl' => 'Sla het evenement eerst op voordat u het tijdschema opbouwt.'],
            'Sluiten' => ['en' => 'Close', 'nl' => 'Sluiten'],
            'Slug' => ['en' => 'Slug', 'nl' => 'Slug'],
            'Source' => ['en' => 'Source', 'nl' => 'Bron'],
            'Source language' => ['en' => 'Source language', 'nl' => 'Brontaal'],
            'Source text' => ['en' => 'Source text', 'nl' => 'Brontekst'],
            'Sleep om te sorteren' => ['en' => 'Drag to sort', 'nl' => 'Sleep om te sorteren'],
            'Status' => ['en' => 'Status', 'nl' => 'Status'],
            'Status Code' => ['en' => 'Status Code', 'nl' => 'Statuscode'],
            'Stock' => ['en' => 'Stock', 'nl' => 'Voorraad'],
            'Subcategorie toevoegen' => ['en' => 'Add subcategory', 'nl' => 'Subcategorie toevoegen'],
            'Synchroniseren' => ['en' => 'Synchronize', 'nl' => 'Synchroniseren'],
            'Synchroniseren...' => ['en' => 'Synchronizing...', 'nl' => 'Synchroniseren...'],
            'System managed' => ['en' => 'System managed', 'nl' => 'Systeembeheer'],
            'Systeemtags' => ['en' => 'System tags', 'nl' => 'Systeemtags'],
            'Taal' => ['en' => 'Language', 'nl' => 'Taal'],
            'Target' => ['en' => 'Target', 'nl' => 'Doel'],
            'Target Url' => ['en' => 'Target URL', 'nl' => 'Doel URL'],
            'Terug' => ['en' => 'Back', 'nl' => 'Terug'],
            'Terug naar overzicht' => ['en' => 'Back to overview', 'nl' => 'Terug naar overzicht'],
            'Tijdschema opgeslagen.' => ['en' => 'Schedule saved.', 'nl' => 'Tijdschema opgeslagen.'],
            'Tijdschema opslaan' => ['en' => 'Save schedule', 'nl' => 'Tijdschema opslaan'],
            'Tijdschema sets' => ['en' => 'Schedule sets', 'nl' => 'Tijdschema sets'],
            'Tijdvenster' => ['en' => 'Time window', 'nl' => 'Tijdvenster'],
            'Titel' => ['en' => 'Title', 'nl' => 'Titel'],
            'Title' => ['en' => 'Title', 'nl' => 'Titel'],
            'Toevoegen' => ['en' => 'Add', 'nl' => 'Toevoegen'],
            'Token login' => ['en' => 'Token login', 'nl' => 'Token login'],
            'Tot :size per selectie' => ['en' => 'Up to :size per selection', 'nl' => 'Tot :size per selectie'],
            'Totale batch' => ['en' => 'Total batch', 'nl' => 'Totale batch'],
            'Totaal gekoppeld' => ['en' => 'Total linked', 'nl' => 'Totaal gekoppeld'],
            'Translation key created.' => ['en' => 'Translation key created.', 'nl' => 'Vertaling aangemaakt.'],
            'Translation key deleted.' => ['en' => 'Translation key deleted.', 'nl' => 'Vertaling verwijderd.'],
            'Translation key saved.' => ['en' => 'Translation key saved.', 'nl' => 'Vertaling opgeslagen.'],
            'Translation autosaved.' => ['en' => 'Translation autosaved.', 'nl' => 'Vertaling automatisch opgeslagen.'],
            'Translation overview' => ['en' => 'Translation overview', 'nl' => 'Vertalingen overzicht'],
            'Translations' => ['en' => 'Translations', 'nl' => 'Vertalingen'],
            'Translations saved.' => ['en' => 'Translations saved.', 'nl' => 'Vertalingen opgeslagen.'],
            'Translations synchronized: :created created, :updated updated.' => ['en' => 'Translations synchronized: :created created, :updated updated.', 'nl' => 'Vertalingen gesynchroniseerd: :created aangemaakt, :updated bijgewerkt.'],
            'Uitklappen' => ['en' => 'Expand', 'nl' => 'Uitklappen'],
            'Uitloggen' => ['en' => 'Log out', 'nl' => 'Uitloggen'],
            'URL' => ['en' => 'URL', 'nl' => 'URL'],
            'URL References' => ['en' => 'URL References', 'nl' => 'URL verwijzingen'],
            'URL overview' => ['en' => 'URL overview', 'nl' => 'URL overzicht'],
            'URL reference overview' => ['en' => 'URL reference overview', 'nl' => 'URL verwijzingen overzicht'],
            'URLs' => ['en' => 'URLs', 'nl' => 'URLs'],
            'Unique Links' => ['en' => 'Unique Links', 'nl' => 'Unieke URLs'],
            'Uploadcapaciteit' => ['en' => 'Upload capacity', 'nl' => 'Uploadcapaciteit'],
            'User Categories' => ['en' => 'User Categories', 'nl' => 'Gebruiker categorieen'],
            'UserCategorie' => ['en' => 'User category', 'nl' => 'Gebruiker categorie'],
            'User category overview' => ['en' => 'User category overview', 'nl' => 'Gebruiker categorieen overzicht'],
            'User overview' => ['en' => 'User overview', 'nl' => 'Gebruikers overzicht'],
            'Users' => ['en' => 'Users', 'nl' => 'Gebruikers'],
            'Vacature' => ['en' => 'Vacancy', 'nl' => 'Vacature'],
            'VacatureCategorie' => ['en' => 'Vacancy category', 'nl' => 'Vacature categorie'],
            'Vacancies' => ['en' => 'Vacancies', 'nl' => 'Vacatures'],
            'Vacancy Categories' => ['en' => 'Vacancy Categories', 'nl' => 'Vacature categorieen'],
            'Vacancy category overview' => ['en' => 'Vacancy category overview', 'nl' => 'Vacature categorieen overzicht'],
            'Vacancy overview' => ['en' => 'Vacancy overview', 'nl' => 'Vacatures overzicht'],
            'Velden' => ['en' => 'Fields', 'nl' => 'Velden'],
            'Verwijderen' => ['en' => 'Delete', 'nl' => 'Verwijderen'],
            'Vestiging' => ['en' => 'Location', 'nl' => 'Vestiging'],
            'VestigingCategorie' => ['en' => 'Location category', 'nl' => 'Vestiging categorie'],
            'Videos' => ['en' => 'Videos', 'nl' => 'Videos'],
            'View' => ['en' => 'View', 'nl' => 'Bekijken'],
            'Voorraad' => ['en' => 'Stock', 'nl' => 'Voorraad'],
            'Wachtwoorden' => ['en' => 'Passwords', 'nl' => 'Wachtwoorden'],
            'Website' => ['en' => 'Website', 'nl' => 'Website'],
            'Website languages' => ['en' => 'Website languages', 'nl' => 'Website talen'],
            'Zoeken' => ['en' => 'Search', 'nl' => 'Zoeken'],
            'Zoeken in alle talen' => ['en' => 'Search in all languages', 'nl' => 'Zoeken in alle talen'],
            'Actiecode' => ['en' => 'Action code', 'nl' => 'Actiecode'],
            'BannerCategorie' => ['en' => 'Banner category', 'nl' => 'Banner categorie'],
            'CatalogusArtikel' => ['en' => 'Catalog product', 'nl' => 'Catalogus artikel'],
            'CatalogusCategorie' => ['en' => 'Catalog category', 'nl' => 'Catalogus categorie'],
            'CatalogusMerk' => ['en' => 'Catalog brand', 'nl' => 'Merk'],
            'CatalogusPromotie' => ['en' => 'Catalog promotion', 'nl' => 'Catalogus promotie'],
            'ContentCategorie' => ['en' => 'Page category', 'nl' => 'Paginacategorie'],
            'Domein' => ['en' => 'Domain', 'nl' => 'Domein'],
            'Download' => ['en' => 'Download', 'nl' => 'Download'],
            'DownloadCategorie' => ['en' => 'Download category', 'nl' => 'Download categorie'],
            'Evenement' => ['en' => 'Event', 'nl' => 'Evenement'],
            'EvenementCategorie' => ['en' => 'Event category', 'nl' => 'Evenement categorie'],
            'Form' => ['en' => 'Form', 'nl' => 'Formulier'],
            'FormCategorie' => ['en' => 'Form category', 'nl' => 'Formulier categorie'],
            'Country' => ['en' => 'Country', 'nl' => 'Land'],
            'Order' => ['en' => 'Order', 'nl' => 'Order'],
            'OrderAfleverData' => ['en' => 'Delivery date', 'nl' => 'Afleverdatum'],
            'Redirect' => ['en' => 'Redirect', 'nl' => 'Redirect'],
            'Review' => ['en' => 'Review', 'nl' => 'Review'],
            'Role' => ['en' => 'Role', 'nl' => 'Rol'],
            'Slider' => ['en' => 'Slider', 'nl' => 'Slider'],
            'SliderCategorie' => ['en' => 'Slider category', 'nl' => 'Slider categorie'],
            'Url' => ['en' => 'URL', 'nl' => 'URL'],
            'Urlverwijzing' => ['en' => 'URL reference', 'nl' => 'URL verwijzing'],
            'User' => ['en' => 'User', 'nl' => 'Gebruiker'],
        ];

        return $defaults + self::supplementalDefaults();
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function supplementalDefaults(): array
    {
        return require __DIR__.'/translation-defaults/backend.php';
    }
}
