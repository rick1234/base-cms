<?php

namespace Tests\Feature\Admin;

use App\Models\Cms\CmsLanguage;
use App\Models\Cms\Country;
use App\Models\Cms\IsoLanguage;
use App\Models\User;
use Database\Seeders\CountryLanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    }

    public function test_admin_can_browse_and_update_countries_with_legacy_fields(): void
    {
        $this->seed(CountryLanguageSeeder::class);

        $admin = User::factory()->admin()->create();
        $country = Country::query()->where('iso2', 'NL')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/isolanden?iso2=NL')
            ->assertOk()
            ->assertSee('Countries')
            ->assertSee('NL');

        $this->actingAs($admin)
            ->post("/admin/isolanden/edit?id={$country->id}", [
                'naam' => 'Netherlands updated',
                'code' => 'NL',
                'iso3' => 'NLD',
                'numeric_code' => '528',
                'currency_code' => 'EUR',
                'active' => '1',
                'is_enabled' => '1',
                'vat' => '1',
                'regio' => 'EU',
                'algemeen' => '1250',
                'envelope' => '500',
                'smallbox' => '750',
                'bigbox' => '1500',
            ])
            ->assertRedirect("/admin/isolanden/{$country->id}/edit");

        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
            'name' => 'Netherlands updated',
            'iso2' => 'NL',
            'charges_vat' => true,
            'region_code' => 'EU',
            'shipping_general_cents' => 1250,
            'shipping_envelope_cents' => 500,
            'shipping_small_box_cents' => 750,
            'shipping_big_box_cents' => 1500,
        ]);
    }

    public function test_admin_can_set_website_languages_and_default_language(): void
    {
        $this->seed(CountryLanguageSeeder::class);

        $admin = User::factory()->admin()->create();
        $english = CmsLanguage::query()->where('code', 'en')->firstOrFail();
        $dutch = CmsLanguage::query()->where('code', 'nl')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/isolanden/talen')
            ->assertOk()
            ->assertSee('Website languages')
            ->assertSee('English');

        $this->actingAs($admin)
            ->post('/admin/isolanden/talen', [
                'enabled_languages' => [$english->id, $dutch->id],
                'default_language' => $dutch->id,
            ])
            ->assertRedirect('/admin/isolanden/talen');

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

    public function test_legacy_cms_country_routes_still_resolve(): void
    {
        $this->seed(CountryLanguageSeeder::class);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/cms/isolanden/index.php?iso2=NL')
            ->assertOk()
            ->assertSee('Countries');

        $this->actingAs($admin)
            ->get('/cms/isolanden/talen/index.php')
            ->assertOk()
            ->assertSee('Website languages');
    }
}
