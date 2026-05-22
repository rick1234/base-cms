<?php

namespace Database\Factories;

use App\Models\Cms\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'navigation_label' => null,
            'excerpt' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'meta_title' => $title,
            'meta_description' => fake()->sentence(12),
            'template' => 'default',
            'status' => 'published',
            'sort_order' => fake()->numberBetween(0, 100),
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
