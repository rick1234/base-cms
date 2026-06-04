<?php

namespace App\Http\Requests\Admin\Faq;

use App\Models\Cms\FaqItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FaqItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $faqItemId = $this->integer('id') ?: null;

        return [
            'id' => ['nullable', 'integer', 'exists:faq_items,id'],
            'question' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('faq_items', 'slug')->ignore($faqItemId)],
            'locale' => ['nullable', 'string', 'max:8'],
            'intro' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:published,draft,archived'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:faq_categories,id'],
            'more_info_navigation_item_id' => ['nullable', 'integer', 'exists:navigation_menu_items,id'],
            'more_info_label' => ['nullable', 'string', 'max:255'],
            'more_info_links_present' => ['nullable', 'boolean'],
            'more_info_links' => ['nullable', 'array'],
            'more_info_links.*.navigation_item_id' => ['nullable', 'integer', 'exists:navigation_menu_items,id'],
            'more_info_links.*.label' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $moreInfoLinks = $this->normalizedMoreInfoLinks();

        $this->merge([
            'question' => $this->input('question', $this->input('vraag')),
            'body' => $this->input('body', $this->input('answer', $this->input('antwoord'))),
            'status' => $this->normalizedStatus($this->input('status', 'published')),
            'active_from' => $this->normalizedDate('active_from', 'startdatum'),
            'active_until' => $this->normalizedDate('active_until', 'einddatum'),
            'categories' => $this->input('categories', $this->input('categorie', [])),
            'more_info_navigation_item_id' => $this->input('more_info_navigation_item_id', data_get($this->input('more_info'), 'navigation_item_id')),
            'more_info_label' => $this->input('more_info_label', data_get($this->input('more_info'), 'label')),
            'more_info_links' => $moreInfoLinks,
        ]);
    }

    public function faqItem(): ?FaqItem
    {
        $id = $this->integer('id');

        return $id > 0 ? FaqItem::query()->findOrFail($id) : null;
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published' => 'published',
            '0', '2', '3', 'inactive', 'draft' => 'draft',
            'archived' => 'archived',
            default => 'published',
        };
    }

    private function normalizedDate(string $key, string $legacyKey): ?string
    {
        $value = $this->input($key, $this->input($legacyKey));

        if (blank($value)) {
            return null;
        }

        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', (string) $value) === 1) {
            return Str::of((string) $value)->explode('-')->reverse()->join('-');
        }

        return (string) $value;
    }

    /**
     * @return list<array{navigation_item_id: mixed, label: mixed}>
     */
    private function normalizedMoreInfoLinks(): array
    {
        $links = $this->input('more_info_links');

        if (is_array($links)) {
            return collect($links)
                ->filter(fn (mixed $link): bool => is_array($link))
                ->map(fn (array $link): array => [
                    'navigation_item_id' => $link['navigation_item_id'] ?? null,
                    'label' => $link['label'] ?? null,
                ])
                ->values()
                ->all();
        }

        if ($this->has('more_info_links_present')) {
            return [];
        }

        $legacyNavigationItemId = $this->input('more_info_navigation_item_id', data_get($this->input('more_info'), 'navigation_item_id'));
        $legacyLabel = $this->input('more_info_label', data_get($this->input('more_info'), 'label'));

        if (blank($legacyNavigationItemId) && blank($legacyLabel)) {
            return [];
        }

        return [[
            'navigation_item_id' => $legacyNavigationItemId,
            'label' => $legacyLabel,
        ]];
    }
}
