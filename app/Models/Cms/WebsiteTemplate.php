<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebsiteTemplate extends CmsModel
{
    use SoftDeletes;

    protected $table = 'website_templates';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'default_settings' => 'array',
            'defined_sections' => 'array',
            'usp_sets' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'website_template_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsWithDefaults(): array
    {
        return [
            ...config('cms_domains.default_template_settings', []),
            ...($this->default_settings ?? []),
        ];
    }

    /**
     * @return list<array{handle: string, label: string, type: string}>
     */
    public function bannerSections(): array
    {
        return collect($this->wireframeSections())
            ->map(fn (array $section): array => [
                'handle' => (string) ($section['handle'] ?? ''),
                'label' => (string) ($section['label'] ?? ''),
                'type' => (string) ($section['type'] ?? 'banner'),
            ])
            ->filter(fn (array $section): bool => $section['handle'] !== '' && in_array($section['type'], ['banner', 'mixed'], true))
            ->values()
            ->all();
    }

    /**
     * @return list<array{handle: string, label: string, type: string}>
     */
    public function wireframeSections(): array
    {
        $sections = collect($this->defined_sections ?? [])
            ->map(fn (array $section): array => [
                'handle' => (string) ($section['handle'] ?? ''),
                'label' => (string) ($section['label'] ?? ''),
                'type' => (string) ($section['type'] ?? 'banner'),
            ])
            ->filter(fn (array $section): bool => $section['handle'] !== '' || $section['label'] !== '')
            ->values();

        if ($sections->isNotEmpty()) {
            return $sections->all();
        }

        return [
            ['handle' => 'homepage_hero', 'label' => __('Homepage hero'), 'type' => 'banner'],
            ['handle' => 'homepage_right_block', 'label' => __('Homepage Right Block'), 'type' => 'banner'],
            ['handle' => 'content_sidebar', 'label' => __('Content sidebar'), 'type' => 'mixed'],
            ['handle' => 'footer_banner', 'label' => __('Footer banner'), 'type' => 'banner'],
        ];
    }

    /**
     * @return list<array{name: string, location: string, items: list<array{label: string, icon: string}>}>
     */
    public function uspSetsForLocation(string $location): array
    {
        return collect($this->usp_sets ?? [])
            ->map(fn (array $set): array => [
                'name' => trim((string) ($set['name'] ?? '')),
                'location' => trim((string) ($set['location'] ?? 'header_top')),
                'items' => collect($set['items'] ?? [])
                    ->map(fn (array $item): array => [
                        'label' => trim((string) ($item['label'] ?? '')),
                        'icon' => trim((string) ($item['icon'] ?? 'done')) ?: 'done',
                    ])
                    ->filter(fn (array $item): bool => $item['label'] !== '')
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $set): bool => $set['location'] === $location && $set['items'] !== [])
            ->values()
            ->all();
    }
}
