<?php

namespace Database\Seeders;

use App\Models\Cms\FaqCategory;
use App\Models\Cms\FaqItem;
use Illuminate\Database\Seeder;

class FaqModuleSeeder extends Seeder
{
    public function run(): void
    {
        $rootCategory = FaqCategory::query()->firstOrCreate(
            ['slug' => 'faq'],
            [
                'name' => 'FAQ',
                'description' => 'Seeded FAQ root category.',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );

        $supportCategory = FaqCategory::query()->firstOrCreate(
            ['slug' => 'support'],
            [
                'parent_id' => $rootCategory->id,
                'name' => 'Support',
                'description' => 'Seeded support FAQ category.',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );

        foreach ($this->faqItems() as $faqData) {
            $faqItem = FaqItem::query()->updateOrCreate(
                ['slug' => $faqData['slug']],
                [
                    ...$faqData,
                    'status' => 'published',
                ],
            );

            $faqItem->categories()->sync([
                $rootCategory->id => ['sort_order' => 1],
                $supportCategory->id => ['sort_order' => 2],
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function faqItems(): array
    {
        return [
            [
                'question' => 'Hoe werkt de vernieuwde FAQ module?',
                'slug' => 'seeded-faq-question',
                'locale' => 'nl',
                'body' => 'Dit voorbeeld controleert dat de FAQ module Nederlandstalige vragen en antwoorden kan beheren.',
                'sort_order' => 1,
            ],
            [
                'question' => 'How does the rebuilt FAQ module work?',
                'slug' => 'seeded-faq-question-en',
                'locale' => 'en',
                'body' => 'This seeded FAQ item verifies that the FAQ module can manage English questions and answers.',
                'sort_order' => 2,
            ],
        ];
    }
}
