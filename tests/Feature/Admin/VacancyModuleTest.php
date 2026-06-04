<?php

namespace Tests\Feature\Admin;

use App\Models\Cms\Vacancy;
use App\Models\Cms\VacancyCategory;
use App\Models\Cms\Form;
use App\Models\User;
use Database\Seeders\VacancyModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VacancyModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_vacancy_routes_use_the_rebuilt_controller_instead_of_the_generic_module_fallback(): void
    {
        $route = Route::getRoutes()->match(Request::create('/admin/vacatures', 'GET'));

        $this->assertSame('admin.vacancies.index', $route->getName());
        $this->assertStringContainsString('VacancyController@index', $route->getActionName());
    }

    public function test_vacancy_module_seeder_creates_multilingual_demo_vacancies_without_duplicates(): void
    {
        $this->seed(VacancyModuleSeeder::class);
        $this->seed(VacancyModuleSeeder::class);

        $this->assertSame(2, VacancyCategory::query()->count());
        $this->assertSame(2, Vacancy::query()->count());
        $this->assertSame(['en', 'nl'], Vacancy::query()->orderBy('locale')->pluck('locale')->all());

        $this->assertDatabaseHas('vacancies', [
            'slug' => 'seeded-cms-specialist',
            'locale' => 'nl',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('vacancies', [
            'slug' => 'seeded-cms-specialist-en',
            'locale' => 'en',
            'status' => 'published',
        ]);

        $vacancy = Vacancy::query()->where('slug', 'seeded-cms-specialist')->firstOrFail();

        $this->assertSame('Nederland', $vacancy->metadata['location']);
        $this->assertSame('jobs@example.com', $vacancy->metadata['contact_email']);
    }

    public function test_admin_can_create_vacancy_with_legacy_fields_categories_and_job_details(): void
    {
        $admin = User::factory()->admin()->create();
        $category = VacancyCategory::query()->create([
            'name' => 'Development',
            'slug' => 'development',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/vacatures/edit', [
                'titel' => 'Laravel developer',
                'taalcode' => 'nl',
                'intro' => 'Werk aan moderne Laravel projecten.',
                'tekst' => '<p>Bouw mee aan het CMS.</p>',
                'status' => '1',
                'startdatum' => '01-06-2026',
                'einddatum' => '30-06-2026',
                'categorie' => [$category->id],
                'locatie' => 'Ede',
                'dienstverband' => 'Fulltime',
                'uren' => '40 uur',
                'salaris' => 'In overleg',
                'sollicitatie_url' => '/vacatures/laravel-developer',
                'contact_email' => 'jobs@example.com',
            ])
            ->assertRedirect('/admin/vacatures/1/edit');

        $vacancy = Vacancy::query()->firstOrFail();

        $this->assertSame('Laravel developer', $vacancy->title);
        $this->assertSame('laravel-developer', $vacancy->slug);
        $this->assertSame('published', $vacancy->status);
        $this->assertSame('Ede', $vacancy->metadata['location']);
        $this->assertSame('Fulltime', $vacancy->metadata['employment_type']);
        $this->assertDatabaseHas('vacancy_category_vacancy', [
            'vacancy_category_id' => $category->id,
            'vacancy_id' => $vacancy->id,
        ]);

        $this->actingAs($admin)
            ->get('/admin/vacatures')
            ->assertOk()
            ->assertSee('Laravel developer')
            ->assertSee('Development');
    }

    public function test_vacancy_slugs_are_deduplicated_when_titles_collide(): void
    {
        $admin = User::factory()->admin()->create();
        Vacancy::query()->create([
            'title' => 'Laravel developer',
            'slug' => 'laravel-developer',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->post('/admin/vacatures/edit', [
                'title' => 'Laravel developer',
                'locale' => 'nl',
                'status' => 'published',
            ])
            ->assertRedirect('/admin/vacatures/2/edit');

        $this->assertDatabaseHas('vacancies', [
            'id' => 2,
            'slug' => 'laravel-developer-2',
        ]);
    }

    public function test_vacancy_seo_tab_uses_path_segments_and_preserves_general_values(): void
    {
        $admin = User::factory()->admin()->create();
        $category = VacancyCategory::query()->create([
            'name' => 'Marketing',
            'slug' => 'marketing',
            'status' => 'active',
        ]);
        $vacancy = Vacancy::query()->create([
            'title' => 'Content marketeer',
            'slug' => 'content-marketeer',
            'locale' => 'nl',
            'body' => 'Original vacancy body.',
            'status' => 'published',
            'metadata' => [
                'location' => 'Arnhem',
            ],
        ]);
        $vacancy->categories()->sync([$category->id => ['sort_order' => 1]]);

        $this->actingAs($admin)
            ->get('/admin/vacatures/'.$vacancy->id.'/edit?tab=seo')
            ->assertRedirect('/admin/vacatures/'.$vacancy->id.'/edit/seo');

        $this->actingAs($admin)
            ->post('/admin/vacatures/'.$vacancy->id, [
                'id' => $vacancy->id,
                'active_tab' => 'seo',
                'meta_title' => 'SEO vacancy title',
                'meta_description' => 'SEO vacancy description.',
            ])
            ->assertRedirect('/admin/vacatures/'.$vacancy->id.'/edit/seo');

        $vacancy->refresh();

        $this->assertSame('Content marketeer', $vacancy->title);
        $this->assertSame('content-marketeer', $vacancy->slug);
        $this->assertSame('published', $vacancy->status);
        $this->assertSame('Arnhem', $vacancy->metadata['location']);
        $this->assertSame('SEO vacancy title', $vacancy->meta_title);
        $this->assertDatabaseHas('vacancy_category_vacancy', [
            'vacancy_category_id' => $category->id,
            'vacancy_id' => $vacancy->id,
        ]);
    }

    public function test_vacancy_edit_uses_simplified_tabs_without_legacy_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $vacancy = Vacancy::query()->create([
            'title' => 'CMS specialist',
            'slug' => 'cms-specialist',
            'locale' => 'nl',
            'body' => 'Vacaturetekst',
            'status' => 'published',
            'metadata' => [
                'location' => 'Nederland',
                'application_url' => '/legacy-sollicitatie',
            ],
        ]);
        Form::query()->create([
            'name' => 'Sollicitatieformulier',
            'slug' => 'sollicitatieformulier',
            'status' => 'published',
        ]);

        $this->assertFalse(Schema::hasColumn('vacancies', 'intro'));

        $this->actingAs($admin)
            ->get("/admin/vacatures/{$vacancy->id}/edit")
            ->assertOk()
            ->assertSee('Algemeen')
            ->assertSee('Formulier')
            ->assertSee('SEO')
            ->assertDontSee('<h2 class="title">Algemeen</h2>', false)
            ->assertDontSee('<label for="slug">URL</label>', false)
            ->assertDontSee('id="slug"', false)
            ->assertDontSee('name="intro"', false)
            ->assertDontSee('Vacaturegegevens')
            ->assertDontSee('Sollicitatie URL')
            ->assertDontSee('/legacy-sollicitatie');

        $this->actingAs($admin)
            ->get("/admin/vacatures/{$vacancy->id}/edit/form")
            ->assertOk()
            ->assertSee('name="active_tab" value="form"', false)
            ->assertSee('Kies een formulier')
            ->assertDontSee('name="title"', false);

        $this->actingAs($admin)
            ->get("/admin/vacatures/{$vacancy->id}/edit/seo")
            ->assertOk()
            ->assertSee('name="meta_title"', false)
            ->assertSee('name="meta_description"', false)
            ->assertDontSee('Open Graph')
            ->assertDontSee('canonical_url', false)
            ->assertDontSee('id="robots"', false)
            ->assertDontSee('<label for="robots"', false);
    }

    public function test_vacancy_categories_support_legacy_fields_and_delete_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/vacatures/categorieen/edit', [
                'naam' => 'Legacy vacancy category',
                'slug' => 'Legacy Vacancy Category',
                'omschrijving' => 'Category body',
                'status' => 1,
                'navigatieHidden' => 1,
            ])
            ->assertRedirect('/admin/vacatures/categorieen/1/edit');

        $category = VacancyCategory::query()->firstOrFail();

        $this->assertSame('legacy-vacancy-category', $category->slug);
        $this->assertTrue($category->is_hidden_from_navigation);

        $this->actingAs($admin)
            ->delete('/admin/vacatures/categorieen/'.$category->id)
            ->assertRedirect('/admin/vacatures/categorieen');

        $this->assertSoftDeleted('vacancy_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_legacy_cms_vacancy_index_resolves_to_the_rebuilt_module(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/cms/vacatures/index.php')
            ->assertOk()
            ->assertSee('Vacancy overview');
    }
}
