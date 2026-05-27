<?php

namespace Tests\Feature\Admin;

use App\Actions\Admin\Translations\SyncTranslationKeys;
use App\Livewire\Admin\Translations\TranslationManager;
use App\Models\Cms\TranslationKey;
use App\Models\User;
use Database\Seeders\CountryLanguageSeeder;
use Database\Seeders\TranslationModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
    }

    public function test_translation_seeder_uses_english_source_and_seeds_dutch_module_labels(): void
    {
        $this->seed(CountryLanguageSeeder::class);
        $this->seed(TranslationModuleSeeder::class);

        $contentKey = TranslationKey::query()
            ->where('area', 'admin')
            ->where('group', '*')
            ->where('key', 'Content')
            ->firstOrFail();

        $contentKey->load('values');

        $this->assertSame('en', $contentKey->source_locale);
        $this->assertSame('Content', $contentKey->source_text);
        $this->assertSame('Inhoud', $contentKey->valueFor('nl')?->value);
        $this->assertSame('Content', $contentKey->valueFor('en')?->value);

        $overviewKey = TranslationKey::query()
            ->where('area', 'admin')
            ->where('group', '*')
            ->where('key', 'Content overview')
            ->firstOrFail();

        $overviewKey->load('values');

        $this->assertSame('Berichten overzicht', $overviewKey->valueFor('nl')?->value);

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

        $this->assertSame('Content item', $modelNameKey->source_text);
        $this->assertSame('Bericht', $modelNameKey->valueFor('nl')?->value);

        $blockPreviewKey = TranslationKey::query()
            ->where('group', '*')
            ->where('key', 'Untitled title block')
            ->firstOrFail();

        $blockPreviewKey->load('values');

        $this->assertSame('Titelloos titelblok', $blockPreviewKey->valueFor('nl')?->value);
        $this->assertSame('Untitled title block', $blockPreviewKey->valueFor('en')?->value);
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
}
