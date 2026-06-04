<?php

namespace Tests\Feature\Admin;

use App\Actions\Admin\Translations\SyncTranslationKeys;
use App\Livewire\Admin\Translations\TranslationManager;
use App\Models\Cms\TranslationKey;
use App\Models\User;
use App\Support\Domains\DomainWizard;
use App\Support\Navigation\NavigationLinkRegistry;
use Database\Seeders\CountryLanguageSeeder;
use Database\Seeders\TranslationModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class TranslationModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_language_seed_sets_dutch_as_default_and_english_as_enabled(): void
    {
        $this->seed(CountryLanguageSeeder::class);

        $this->assertDatabaseHas('languages', [
            'code' => 'nl',
            'status' => 'active',
            'is_enabled' => true,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('languages', [
            'code' => 'en',
            'status' => 'active',
            'is_enabled' => true,
            'is_default' => false,
        ]);
    }

    public function test_admin_can_create_and_edit_translation_values(): void
    {
        $this->seed(CountryLanguageSeeder::class);
        $admin = User::factory()->admin()->create(['locale' => 'nl']);

        $this->actingAs($admin);

        Livewire::test(TranslationManager::class, ['initialLocale' => 'en'])
            ->call('newKey')
            ->set('form.area', 'admin')
            ->set('form.group', '*')
            ->set('form.key', 'Save')
            ->set('form.source_locale', 'en')
            ->set('form.source_text', 'Save')
            ->set('form.values.nl.value', 'Opslaan')
            ->set('form.values.nl.is_reviewed', true)
            ->set('form.values.en.value', 'Save')
            ->set('form.values.en.is_reviewed', true)
            ->call('saveKey')
            ->assertSet('editingId', 1);

        $this->assertDatabaseHas('translation_keys', [
            'area' => 'admin',
            'group' => '*',
            'key' => 'Save',
            'source_locale' => 'en',
        ]);

        $this->assertDatabaseHas('translation_values', [
            'translation_key_id' => 1,
            'locale' => 'en',
            'value' => 'Save',
            'is_reviewed' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/translations?locale=en')
            ->assertOk()
            ->assertSee('Translations')
            ->assertSee('Save');
    }

    public function test_livewire_autosaves_selected_locale_value(): void
    {
        $this->seed(CountryLanguageSeeder::class);
        $admin = User::factory()->admin()->create(['locale' => 'nl']);
        $translationKey = TranslationKey::query()->create([
            'area' => 'frontend',
            'group' => '*',
            'key' => 'Home',
            'source_locale' => 'en',
            'source_text' => 'Home',
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        Livewire::test(TranslationManager::class, ['initialLocale' => 'en'])
            ->set("values.{$translationKey->id}", 'Homepage')
            ->assertSet("values.{$translationKey->id}", 'Homepage');

        $this->assertDatabaseHas('translation_values', [
            'translation_key_id' => $translationKey->id,
            'locale' => 'en',
            'value' => 'Homepage',
        ]);
    }

    public function test_runtime_loader_uses_admin_and_frontend_translation_areas(): void
    {
        $this->seed(CountryLanguageSeeder::class);

        $adminKey = TranslationKey::query()->create([
            'area' => 'admin',
            'group' => '*',
            'key' => 'Dashboard',
            'source_locale' => 'en',
            'source_text' => 'Dashboard',
            'status' => 'active',
        ]);
        $adminKey->values()->create([
            'locale' => 'en',
            'value' => 'Control panel',
            'status' => 'active',
        ]);

        $frontendKey = TranslationKey::query()->create([
            'area' => 'frontend',
            'group' => '*',
            'key' => 'Home',
            'source_locale' => 'en',
            'source_text' => 'Home',
            'status' => 'active',
        ]);
        $frontendKey->values()->create([
            'locale' => 'en',
            'value' => 'Homepage',
            'status' => 'active',
        ]);

        $admin = User::factory()->admin()->create(['locale' => 'en']);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Control panel');

        $this->withSession(['locale' => 'en'])
            ->get('/')
            ->assertOk()
            ->assertSee('Homepage');
    }

    public function test_runtime_loader_treats_livewire_updates_from_admin_pages_as_admin_area(): void
    {
        $request = Request::create('/livewire/update', 'POST', server: [
            'HTTP_REFERER' => url('/admin/content/3/edit'),
        ]);

        $this->app->instance('request', $request);

        $this->assertSame('admin', app(\App\Support\Localization\TranslationRepository::class)->activeArea());
    }

    public function test_enabled_language_can_be_selected_for_current_user(): void
    {
        $this->seed(CountryLanguageSeeder::class);
        $admin = User::factory()->admin()->create(['locale' => 'nl']);

        $this->actingAs($admin)
            ->from('/admin')
            ->post('/locale/en')
            ->assertRedirect('/admin');

        $this->assertSame('en', $admin->fresh()->locale);
        $this->assertSame('en', session('locale'));
    }

    public function test_admin_language_switcher_uses_flags_instead_of_language_codes(): void
    {
        $this->seed(CountryLanguageSeeder::class);
        $admin = User::factory()->admin()->create(['locale' => 'nl']);

        $response = $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('admin-language-switcher', false)
            ->assertSee('vendor/flag-icons/flags/4x3/nl.svg', false)
            ->assertSee('vendor/flag-icons/flags/4x3/gb.svg', false);

        $this->assertStringNotContainsString('>NL<', $response->getContent());
        $this->assertStringNotContainsString('>EN<', $response->getContent());
    }

    public function test_sync_imports_literal_translation_strings(): void
    {
        $this->seed(CountryLanguageSeeder::class);

        $result = app(SyncTranslationKeys::class)->handle();

        $this->assertGreaterThan(0, $result['scanned']);
        $this->assertDatabaseHas('translation_keys', [
            'area' => 'admin',
            'group' => '*',
            'key' => 'Opslaan',
            'source_locale' => 'en',
            'source_text' => 'Save',
        ]);

        $translationKey = TranslationKey::query()
            ->where('area', 'admin')
            ->where('group', '*')
            ->where('key', 'Opslaan')
            ->firstOrFail();

        $this->assertDatabaseHas('translation_values', [
            'translation_key_id' => $translationKey->id,
            'locale' => 'en',
            'value' => 'Save',
        ]);

        $this->assertDatabaseHas('translation_keys', [
            'area' => 'admin',
            'group' => '*',
            'key' => 'Primary recipient',
            'source_text' => 'Primary recipient',
        ]);

        $this->assertDatabaseHas('translation_keys', [
            'area' => 'admin',
            'group' => '*',
            'key' => 'Bijlage opgeslagen.',
            'source_text' => 'Attachment saved.',
        ]);

        $this->assertDatabaseHas('translation_keys', [
            'area' => 'admin',
            'group' => '*',
            'key' => 'Social & contact',
            'source_text' => 'Social & contact',
        ]);

        $this->assertDatabaseHas('translation_keys', [
            'area' => 'admin',
            'group' => '*',
            'key' => 'Catalog category',
            'source_text' => 'Catalog category',
        ]);

        $this->assertDatabaseHas('translation_keys', [
            'area' => 'admin',
            'group' => '*',
            'key' => ':count banner uploaded.|:count banners uploaded.',
            'source_text' => ':count banner uploaded.|:count banners uploaded.',
        ]);
    }

    public function test_translation_defaults_cover_backend_strings(): void
    {
        $defaults = array_fill_keys(array_keys(TranslationModuleSeeder::defaults()), true);
        $missing = collect($this->backendTranslationStrings())
            ->reject(fn (string $key): bool => isset($defaults[$key]))
            ->sort()
            ->values()
            ->all();

        $this->assertSame([], $missing);
    }

    public function test_backend_flash_messages_are_translatable_and_consistent(): void
    {
        $rawFlashMessages = collect($this->backendControllerFiles())
            ->flatMap(function (\SplFileInfo $file): array {
                preg_match_all('/flash\\(\\s*([\'"])/u', File::get($file->getPathname()), $matches);

                return array_fill(0, count($matches[1] ?? []), $file->getPathname());
            })
            ->values()
            ->all();

        $this->assertSame([], $rawFlashMessages);

        $legacyUpdatedSaveMessages = collect($this->backendFlashTranslationStrings())
            ->intersect([
                'Domain updated.',
                'Page updated.',
                'Record updated.',
                'Template updated.',
            ])
            ->values()
            ->all();

        $this->assertSame([], $legacyUpdatedSaveMessages);
    }

    public function test_translation_seeder_uses_english_source_and_seeds_dutch_module_labels(): void
    {
        $this->seed(CountryLanguageSeeder::class);
        $this->seed(TranslationModuleSeeder::class);

        $pagesKey = TranslationKey::query()
            ->where('area', 'admin')
            ->where('group', '*')
            ->where('key', 'Pages')
            ->firstOrFail();

        $pagesKey->load('values');

        $this->assertSame('en', $pagesKey->source_locale);
        $this->assertSame('Pages', $pagesKey->source_text);
        $this->assertSame('Pagina\'s', $pagesKey->valueFor('nl')?->value);
        $this->assertSame('Pages', $pagesKey->valueFor('en')?->value);

        $overviewKey = TranslationKey::query()
            ->where('area', 'admin')
            ->where('group', '*')
            ->where('key', 'Pages overview')
            ->firstOrFail();

        $overviewKey->load('values');

        $this->assertSame('Pagina\'s overzicht', $overviewKey->valueFor('nl')?->value);

        $legacyDutchKey = TranslationKey::query()
            ->where('area', 'admin')
            ->where('group', '*')
            ->where('key', 'Toevoegen')
            ->firstOrFail();

        $legacyDutchKey->load('values');

        $this->assertSame('en', $legacyDutchKey->source_locale);
        $this->assertSame('Add', $legacyDutchKey->source_text);
        $this->assertSame('Toevoegen', $legacyDutchKey->valueFor('nl')?->value);
        $this->assertSame('Add', $legacyDutchKey->valueFor('en')?->value);

        $imageOrderKey = TranslationKey::query()
            ->where('group', '*')
            ->where('key', 'Image order saved.')
            ->firstOrFail();

        $imageOrderKey->load('values');

        $this->assertSame('Afbeeldingsvolgorde opgeslagen.', $imageOrderKey->valueFor('nl')?->value);
        $this->assertSame('Image order saved.', $imageOrderKey->valueFor('en')?->value);

        $modelNameKey = TranslationKey::query()
            ->where('group', '*')
            ->where('key', 'ContentItem')
            ->firstOrFail();

        $modelNameKey->load('values');

        $this->assertSame('Page', $modelNameKey->source_text);
        $this->assertSame('Pagina', $modelNameKey->valueFor('nl')?->value);

        $blockPreviewKey = TranslationKey::query()
            ->where('group', '*')
            ->where('key', 'Untitled title block')
            ->firstOrFail();

        $blockPreviewKey->load('values');

        $this->assertSame('Titelloos titelblok', $blockPreviewKey->valueFor('nl')?->value);
        $this->assertSame('Untitled title block', $blockPreviewKey->valueFor('en')?->value);

        foreach (['Domain updated.', 'Event duplicated.', 'Page updated.', 'Record updated.', 'Template updated.'] as $obsoleteFlashKey) {
            $this->assertDatabaseMissing('translation_keys', [
                'key' => $obsoleteFlashKey,
            ]);
        }

        foreach (['Content overview', 'Berichten overzicht', 'Content item', 'Edit content item'] as $obsoleteContentKey) {
            $this->assertDatabaseMissing('translation_keys', [
                'key' => $obsoleteContentKey,
            ]);
        }

        foreach (['Bigbox', 'Envelope', 'Smallbox', 'Verzendkosten'] as $obsoleteCountryShippingKey) {
            $this->assertDatabaseMissing('translation_keys', [
                'key' => $obsoleteCountryShippingKey,
            ]);
        }

        foreach (['Localization data refreshed: :countries countries and :languages languages.', 'Refresh package data'] as $obsoleteCountryPackageRefreshKey) {
            $this->assertDatabaseMissing('translation_keys', [
                'key' => $obsoleteCountryPackageRefreshKey,
            ]);
        }

        foreach ([
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
        ] as $obsoleteEmptyStateKey) {
            $this->assertDatabaseMissing('translation_keys', [
                'key' => $obsoleteEmptyStateKey,
            ]);
        }
    }

    public function test_legacy_cms_translation_routes_resolve(): void
    {
        $this->seed(CountryLanguageSeeder::class);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/cms/translations/index.php')
            ->assertOk()
            ->assertSee('Translations');
    }

    /**
     * @return list<string>
     */
    private function backendTranslationStrings(): array
    {
        return collect([
            resource_path('views/admin'),
            resource_path('views/livewire/admin'),
            resource_path('views/layouts/admin.blade.php'),
            app_path('Actions/Admin'),
            app_path('Http/Controllers/Admin'),
            app_path('Http/Requests/Admin'),
            app_path('Livewire/Admin'),
            base_path('routes/admin.php'),
            base_path('routes/cms.php'),
        ])
            ->filter(fn (string $path): bool => File::exists($path))
            ->flatMap(fn (string $path): array => File::isDirectory($path) ? File::allFiles($path) : [new \SplFileInfo($path)])
            ->filter(fn (\SplFileInfo $file): bool => $file->getExtension() === 'php')
            ->flatMap(function (\SplFileInfo $file): array {
                preg_match_all("/(?:__|@lang|trans_choice)\\(\\s*(['\"])((?:\\\\.|(?!\\1).)*)\\1/u", File::get($file->getPathname()), $matches);

                return collect($matches[2] ?? [])
                    ->map(fn (string $value): string => stripcslashes($value))
                    ->filter(fn (string $value): bool => trim($value) !== '')
                    ->all();
            })
            ->merge($this->configuredBackendTranslationStrings())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function backendFlashTranslationStrings(): array
    {
        return collect($this->backendControllerFiles())
            ->flatMap(function (\SplFileInfo $file): array {
                preg_match_all(
                    "/flash\\(.*?(?:__|trans_choice)\\(\\s*(['\"])((?:\\\\.|(?!\\1).)*)\\1/s",
                    File::get($file->getPathname()),
                    $matches,
                );

                return collect($matches[2] ?? [])
                    ->map(fn (string $value): string => stripcslashes($value))
                    ->filter(fn (string $value): bool => trim($value) !== '')
                    ->all();
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<\SplFileInfo>
     */
    private function backendControllerFiles(): array
    {
        return collect([app_path('Http/Controllers/Admin')])
            ->filter(fn (string $path): bool => File::exists($path))
            ->flatMap(fn (string $path): array => File::allFiles($path))
            ->filter(fn (\SplFileInfo $file): bool => $file->getExtension() === 'php')
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function configuredBackendTranslationStrings(): array
    {
        $strings = collect(config('cms_modules.groups', []))->values();

        foreach (['modules', 'screens'] as $section) {
            collect(config("cms_modules.{$section}", []))
                ->each(function (array $definition) use ($strings): void {
                    $strings->push($definition['name'] ?? null);
                    $strings->push($definition['description'] ?? null);

                    collect($definition['pages'] ?? [])
                        ->each(fn (array $page) => $strings->push($page['name'] ?? null));
                });
        }

        collect(config('cms_modules.root_pages', []))
            ->each(fn (array $page) => $strings->push($page['name'] ?? null));

        collect(config('cms_modules.legacy_classes', []))
            ->keys()
            ->each(fn (string $legacyClass) => $strings->push($legacyClass));

        collect(config('cms_categories', []))
            ->pluck('linked_label')
            ->each(fn (string $label) => $strings->push($label));

        return collect(DomainWizard::steps())
            ->pluck('label')
            ->merge(app(NavigationLinkRegistry::class)->definitionLabels())
            ->merge($strings)
            ->merge(config('cms_domains.button_styles', []))
            ->merge(config('cms_domains.placement_options', []))
            ->merge(config('cms_domains.social_platforms', []))
            ->merge(config('cms_domains.public_integrations', []))
            ->merge([
                'Logo path',
                'Hero image path',
                'Show USP bar',
                'Sticky header',
                'Show hero',
                'Show footer credit',
                'Search enabled',
                'Contact title',
                'Contact text',
                'Social title',
                'Social text',
                'Content title',
                'Credit label',
                'Credit URL',
                'Primary color',
                'Secondary color',
                'Tertiary color',
                'Accent color',
                'Surface color',
                'Canvas color',
                'Light color',
                'Grey color',
                'Dark color',
                'Ink color',
                'Muted ink color',
                'Base font family',
                'Heading font family',
                'Button radius',
                'Content width',
                'Wrapper width',
                'Logo width',
                'Logo height',
                'Hero height',
                'Strong',
                'Quiet',
                'Editorial',
                'Active',
                'Change link',
                'Custom URL',
                'Edit source',
                'Linked item',
                'Move item',
                'Navigation title',
                'No results found.',
                'Remove item',
                'Search failed.',
                'Searching...',
                'Select',
                'Use subcategories as submenu',
            ])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->unique()
            ->values()
            ->all();
    }
}
