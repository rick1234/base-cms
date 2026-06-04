<?php

namespace Tests\Feature\Admin;

use App\Models\Cms\CmsLanguage;
use App\Models\Cms\Country;
use App\Models\Cms\IsoLanguage;
use App\Models\User;
use Database\Seeders\CountryLanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CountryLanguageModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_language_seeder_imports_package_data_without_duplicates(): void
    {
        $this->seed(CountryLanguageSeeder::class);
        $this->seed(CountryLanguageSeeder::class);

        $this->assertGreaterThan(240, Country::query()->count());
        $this->assertGreaterThan(300, CmsLanguage::query()->count());
        $this->assertSame(CmsLanguage::query()->count(), IsoLanguage::query()->count());

        $this->assertDatabaseHas('countries', [
            'iso2' => 'NL',
            'iso3' => 'NLD',
            'numeric_code' => '528',
            'currency_code' => 'EUR',
            'status' => 'active',
            'is_enabled' => true,
        ]);

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

        $this->assertDatabaseHas('languages', [
            'code' => 'nl',
            'direction' => 'ltr',
        ]);

        foreach ([
            'shipping_general_cents',
            'shipping_envelope_cents',
            'shipping_small_box_cents',
            'shipping_big_box_cents',
        ] as $column) {
            $this->assertFalse(Schema::hasColumn('countries', $column), "The countries table should not include {$column}.");
        }
    }

    public function test_admin_can_browse_and_update_countries_with_legacy_fields(): void
    {
        $this->seed(CountryLanguageSeeder::class);

        $admin = User::factory()->admin()->create();
        $country = Country::query()->where('iso2', 'NL')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/landen?iso2=NL')
            ->assertOk()
            ->assertSee('Countries')
            ->assertSee('NL')
            ->assertDontSee('Refresh package data')
            ->assertDontSee('Pakketgegevens vernieuwen');

        $this->actingAs($admin)
            ->post('/admin/landen/sync')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get("/admin/landen/{$country->id}/edit")
            ->assertOk()
            ->assertDontSee('Shipping costs')
            ->assertDontSee('Verzendkosten')
            ->assertDontSee('shipping_general_cents', false)
            ->assertDontSee('shipping_envelope_cents', false)
            ->assertDontSee('shipping_small_box_cents', false)
            ->assertDontSee('shipping_big_box_cents', false);

        $this->actingAs($admin)
            ->post("/admin/landen/edit?id={$country->id}", [
                'naam' => 'Netherlands updated',
                'code' => 'NL',
                'iso3' => 'NLD',
                'numeric_code' => '528',
                'currency_code' => 'EUR',
                'active' => '1',
                'is_enabled' => '1',
                'vat' => '1',
                'regio' => 'EU',
            ])
            ->assertRedirect("/admin/landen/{$country->id}/edit");

        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
            'name' => 'Netherlands updated',
            'iso2' => 'NL',
            'charges_vat' => true,
            'region_code' => 'EU',
        ]);
    }

    public function test_admin_can_set_website_languages_and_default_language(): void
    {
        $this->seed(CountryLanguageSeeder::class);

        $admin = User::factory()->admin()->create();
        $english = CmsLanguage::query()->where('code', 'en')->firstOrFail();
        $dutch = CmsLanguage::query()->where('code', 'nl')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/landen/talen')
            ->assertOk()
            ->assertSee('Website languages')
            ->assertSee('English');

        $this->actingAs($admin)
            ->post('/admin/landen/talen', [
                'enabled_languages' => [$english->id, $dutch->id],
                'default_language' => $dutch->id,
            ])
            ->assertRedirect('/admin/landen/talen');

        $this->assertDatabaseHas('languages', [
            'id' => $dutch->id,
            'is_enabled' => true,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('languages', [
            'id' => $english->id,
            'is_enabled' => true,
            'is_default' => false,
        ]);
    }

    public function test_language_overview_and_edit_pages_use_flags_for_language_codes(): void
    {
        $this->seed(CountryLanguageSeeder::class);

        $admin = User::factory()->admin()->create();
        $dutch = CmsLanguage::query()->where('code', 'nl')->firstOrFail();

        $overview = $this->actingAs($admin)
            ->get('/admin/landen/talen')
            ->assertOk()
            ->assertSee('vendor/flag-icons/flags/4x3/nl.svg', false)
            ->assertSee('vendor/flag-icons/flags/4x3/gb.svg', false)
            ->assertSee('language-summary-item', false)
            ->assertDontSee('>nl<', false);

        $this->assertStringNotContainsString('>en<', $overview->getContent());

        $this->actingAs($admin)
            ->get("/admin/landen/talen/{$dutch->id}/edit")
            ->assertOk()
            ->assertSee('language-code-field', false)
            ->assertSee('vendor/flag-icons/flags/4x3/nl.svg', false);
    }

    public function test_cms_country_routes_still_resolve(): void
    {
        $this->seed(CountryLanguageSeeder::class);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/cms/landen/index.php?iso2=NL')
            ->assertOk()
            ->assertSee('Countries');

        $this->actingAs($admin)
            ->get('/cms/landen/talen/index.php')
            ->assertOk()
            ->assertSee('Website languages');
    }
}
