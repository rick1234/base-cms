<?php

namespace Database\Seeders;

use App\Models\Cms\Vacancy;
use App\Models\Cms\VacancyCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class VacancyModuleSeeder extends Seeder
{
    public function run(): void
    {
        $actorId = User::query()->where('is_admin', true)->value('id');

        $rootCategory = VacancyCategory::query()->firstOrCreate(
            ['slug' => 'vacatures'],
            [
                'name' => 'Vacatures',
                'description' => 'Seeded vacancy root category.',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ],
        );

        $teamCategory = VacancyCategory::query()->firstOrCreate(
            ['slug' => 'team'],
            [
                'parent_id' => $rootCategory->id,
                'name' => 'Team',
                'description' => 'Seeded team vacancy category.',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ],
        );

        foreach ($this->vacancies() as $index => $data) {
            $vacancy = Vacancy::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    ...$data,
                    'status' => 'published',
                    'sort_order' => $index + 1,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ],
            );

            $vacancy->categories()->sync([
                $rootCategory->id => ['sort_order' => 1],
                $teamCategory->id => ['sort_order' => 2],
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function vacancies(): array
    {
        return [
            [
                'title' => 'CMS specialist',
                'slug' => 'seeded-cms-specialist',
                'locale' => 'nl',
                'body' => 'Deze voorbeeldvacature controleert dat de vacaturemodule Nederlandstalige inhoud kan beheren.',
                'meta_description' => 'Voorbeeldvacature voor een CMS specialist in de Nederlandstalige vacaturemodule.',
                'meta_title' => 'CMS specialist vacature',
                'metadata' => [
                    'location' => 'Nederland',
                    'employment_type' => 'Fulltime',
                    'hours' => '32-40 uur',
                    'salary' => 'In overleg',
                    'contact_email' => 'jobs@example.com',
                ],
            ],
            [
                'title' => 'CMS specialist',
                'slug' => 'seeded-cms-specialist-en',
                'locale' => 'en',
                'body' => 'This seeded vacancy verifies that the vacancy module can manage English content.',
                'meta_description' => 'Seeded vacancy for a CMS specialist in the English vacancy module.',
                'meta_title' => 'CMS specialist vacancy',
                'metadata' => [
                    'location' => 'Netherlands',
                    'employment_type' => 'Full time',
                    'hours' => '32-40 hours',
                    'salary' => 'By agreement',
                    'contact_email' => 'jobs@example.com',
                ],
            ],
        ];
    }
}
