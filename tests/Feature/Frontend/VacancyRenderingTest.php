<?php

namespace Tests\Feature\Frontend;

use App\Models\Cms\CmsLanguage;
use App\Models\Cms\Vacancy;
use App\Models\Cms\VacancyAttachment;
use App\Models\Cms\VacancyCategory;
use Database\Seeders\CountryLanguageSeeder;
use Database\Seeders\TranslationModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VacancyRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_vacancies_overview_renders_published_vacancies_and_filters_by_category(): void
    {
        $team = VacancyCategory::query()->create([
            'name' => 'Team',
            'slug' => 'team',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $support = VacancyCategory::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => 'active',
            'sort_order' => 2,
        ]);

        $developer = Vacancy::query()->create([
            'title' => 'Laravel developer',
            'slug' => 'laravel-developer',
            'locale' => 'nl',
            'body' => 'Build maintainable CMS features.',
            'status' => 'published',
            'active_from' => now()->subDay(),
            'sort_order' => 1,
            'metadata' => [
                'location' => 'Rotterdam',
                'employment_type' => 'Fulltime',
                'hours' => '32-40 uur',
                'work_mode' => 'hybrid',
            ],
        ]);
        $developer->categories()->attach($team->id, ['sort_order' => 1]);

        $supportVacancy = Vacancy::query()->create([
            'title' => 'Support specialist',
            'slug' => 'support-specialist',
            'locale' => 'nl',
            'body' => 'Help CMS users succeed.',
            'status' => 'published',
            'active_from' => now()->subDay(),
            'sort_order' => 2,
        ]);
        $supportVacancy->categories()->attach($support->id, ['sort_order' => 1]);

        Vacancy::query()->create([
            'title' => 'Draft vacancy',
            'slug' => 'draft-vacancy',
            'locale' => 'nl',
            'status' => 'draft',
        ]);

        $this->get('/vacancies')
            ->assertOk()
            ->assertSee('Laravel developer')
            ->assertSee('Rotterdam')
            ->assertSee('Hybrid')
            ->assertSee('Support specialist')
            ->assertDontSee('Draft vacancy')
            ->assertSee('href="'.route('frontend.vacancies.show', ['vacancy' => 'laravel-developer']).'"', false)
            ->assertSee('category=team', false);

        $this->get('/vacancies?category=team')
            ->assertOk()
            ->assertSee('Laravel developer')
            ->assertDontSee('Support specialist');
    }

    public function test_vacancy_detail_renders_content_metadata_attachments_and_seo_metadata(): void
    {
        $category = VacancyCategory::query()->create([
            'name' => 'Engineering',
            'slug' => 'engineering',
            'status' => 'active',
        ]);

        $vacancy = Vacancy::query()->create([
            'title' => 'Frontend engineer',
            'slug' => 'frontend-engineer',
            'locale' => 'nl',
            'body' => '<p>Build accessible interfaces.</p><script>bad()</script>',
            'meta_title' => 'Frontend engineer vacancy',
            'meta_description' => 'Frontend engineer meta description.',
            'status' => 'published',
            'active_from' => now()->subDay(),
            'metadata' => [
                'location' => 'Utrecht',
                'vacancy_type' => 'paid',
                'employment_type' => 'Parttime',
                'work_mode' => 'remote',
                'hours' => '24-32 uur',
                'salary' => 'In overleg',
                'contact_email' => 'jobs@example.com',
            ],
        ]);
        $vacancy->categories()->attach($category->id, ['sort_order' => 1]);

        VacancyAttachment::query()->create([
            'vacancy_id' => $vacancy->id,
            'name' => 'Role profile',
            'type' => 'application/pdf',
            'url' => 'storage/vacancies/attachments/profile.pdf',
            'sort_order' => 1,
        ]);

        $response = $this->get('/vacancies/frontend-engineer')
            ->assertOk()
            ->assertSee('Frontend engineer vacancy')
            ->assertSee('Frontend engineer meta description.')
            ->assertSee('Build accessible interfaces.')
            ->assertSee('Utrecht')
            ->assertSee('Betaalde functie')
            ->assertSee('Remote')
            ->assertSee('jobs@example.com')
            ->assertSee('Engineering')
            ->assertSee('Role profile')
            ->assertSee('application/ld+json', false);

        $this->assertStringNotContainsString('<script>bad()</script>', $response->getContent());
    }

    public function test_localized_vacancy_routes_use_the_active_locale(): void
    {
        $this->seed(CountryLanguageSeeder::class);
        $this->seed(TranslationModuleSeeder::class);

        CmsLanguage::query()->updateOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'slug' => 'english',
                'native_name' => 'English',
                'direction' => 'ltr',
                'status' => 'active',
                'is_enabled' => true,
                'is_default' => false,
            ],
        );

        Vacancy::query()->create([
            'title' => 'Nederlandse vacature',
            'slug' => 'nederlandse-vacature',
            'locale' => 'nl',
            'status' => 'published',
            'active_from' => now()->subDay(),
        ]);

        Vacancy::query()->create([
            'title' => 'English vacancy',
            'slug' => 'english-vacancy',
            'locale' => 'en',
            'status' => 'published',
            'active_from' => now()->subDay(),
        ]);

        $this->get('/en/vacancies')
            ->assertOk()
            ->assertSee('English vacancy')
            ->assertDontSee('Nederlandse vacature');

        $this->get('/en/vacancies/english-vacancy')
            ->assertOk()
            ->assertSee('English vacancy');

        $this->get('/en/vacancies/nederlandse-vacature')->assertNotFound();
    }

    public function test_inactive_vacancies_are_not_publicly_available(): void
    {
        Vacancy::query()->create([
            'title' => 'Expired vacancy',
            'slug' => 'expired-vacancy',
            'locale' => 'nl',
            'status' => 'published',
            'active_from' => now()->subMonth(),
            'active_until' => now()->subDay(),
        ]);

        $this->get('/vacancies')
            ->assertOk()
            ->assertDontSee('Expired vacancy');

        $this->get('/vacancies/expired-vacancy')->assertNotFound();
    }
}
