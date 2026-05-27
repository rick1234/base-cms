<?php

namespace App\Http\Requests\Admin\Banners;

use App\Models\Cms\Banner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class BannerRequest extends FormRequest
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
        return [
            'id' => ['nullable', 'integer', 'exists:banners,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'string', 'max:255'],
            'button_text' => ['nullable', 'string', 'max:255'],
            'text' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:published,draft,archived'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'string', 'in:_self,_blank'],
            'delete_image' => ['boolean'],
            'image' => ['nullable', 'image', 'max:10240'],
            'afbeelding' => ['nullable', 'image', 'max:10240'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:banner_categories,id'],
            'translations' => ['array'],
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.subtitle' => ['nullable', 'string', 'max:255'],
            'translations.*.link_url' => ['nullable', 'string', 'max:255'],
            'translations.*.button_text' => ['nullable', 'string', 'max:255'],
            'translations.*.content' => ['nullable', 'string'],
            'translations.*.alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $translations = (array) $this->input('translations', []);

        foreach (['nl', 'en', 'de', 'fr'] as $locale) {
            $translations[$locale] ??= [
                'title' => $this->input('titel'.$locale),
                'subtitle' => $this->input('subtitel'.$locale),
                'link_url' => $this->input('link'.$locale),
                'button_text' => $this->input('knoptekst'.$locale),
                'content' => $this->input('tekst'.$locale),
            ];
        }

        $primary = $translations[app()->getLocale()] ?? $translations['nl'] ?? collect($translations)->first() ?? [];

        $this->merge([
            'title' => $this->input('title', $this->input('titel', $primary['title'] ?? null)),
            'link_url' => $this->input('link_url', $this->input('link', $primary['link_url'] ?? null)),
            'button_text' => $this->input('button_text', $this->input('knoptekst', $primary['button_text'] ?? null)),
            'text' => $this->input('text', $this->input('tekst', $primary['content'] ?? null)),
            'status' => $this->normalizedStatus($this->input('status', 'published')),
            'starts_at' => $this->normalizedDate('starts_at', 'startdatum'),
            'ends_at' => $this->normalizedDate('ends_at', 'einddatum'),
            'categories' => $this->input('categories', $this->input('categorie', [])),
            'delete_image' => $this->boolean('delete_image'),
            'translations' => $translations,
        ]);
    }

    public function banner(): ?Banner
    {
        $id = $this->integer('id');

        return $id > 0 ? Banner::query()->findOrFail($id) : null;
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'online', 'published' => 'published',
            '0', '2', '3', 'inactive', 'offline', 'draft' => 'draft',
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
}
