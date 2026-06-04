<?php

namespace App\Http\Requests\Admin\Banners;

use App\Models\Cms\Banner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'active_tab' => ['sometimes', Rule::in(['general', 'image', 'translations'])],
            'saveAndStay' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $activeTab = $this->string('active_tab')->toString() ?: 'general';
        $data = ['active_tab' => $activeTab];
        $translations = (array) $this->input('translations', []);
        $hasTranslations = $this->has('translations');

        foreach (['nl', 'en', 'de', 'fr'] as $locale) {
            $legacyKeys = [
                'titel'.$locale,
                'subtitel'.$locale,
                'link'.$locale,
                'knoptekst'.$locale,
                'tekst'.$locale,
            ];

            if (! $this->hasAny($legacyKeys)) {
                continue;
            }

            $hasTranslations = true;
            $translations[$locale] ??= [];
            $translations[$locale] = [
                ...$translations[$locale],
                'title' => $this->input('titel'.$locale),
                'subtitle' => $this->input('subtitel'.$locale),
                'link_url' => $this->input('link'.$locale),
                'button_text' => $this->input('knoptekst'.$locale),
                'content' => $this->input('tekst'.$locale),
            ];
        }

        if ($hasTranslations) {
            $primary = $translations[app()->getLocale()] ?? $translations['nl'] ?? collect($translations)->first() ?? [];

            $data['translations'] = $translations;
            $data['title'] = $this->input('title', $this->input('titel', $primary['title'] ?? null));
            $data['link_url'] = $this->input('link_url', $this->input('link', $primary['link_url'] ?? null));
            $data['button_text'] = $this->input('button_text', $this->input('knoptekst', $primary['button_text'] ?? null));
            $data['text'] = $this->input('text', $this->input('tekst', $primary['content'] ?? null));
        } else {
            foreach ([
                'title' => ['title', 'titel'],
                'link_url' => ['link_url', 'link'],
                'button_text' => ['button_text', 'knoptekst'],
                'text' => ['text', 'tekst'],
            ] as $field => $keys) {
                if ($this->hasAny($keys)) {
                    $data[$field] = $this->input($keys[0], $this->input($keys[1]));
                }
            }
        }

        if ($this->has('status')) {
            $data['status'] = $this->normalizedStatus($this->input('status', 'published'));
        }

        if ($this->hasAny(['starts_at', 'startdatum'])) {
            $data['starts_at'] = $this->normalizedDate('starts_at', 'startdatum');
        }

        if ($this->hasAny(['ends_at', 'einddatum'])) {
            $data['ends_at'] = $this->normalizedDate('ends_at', 'einddatum');
        }

        if ($this->has('sort_order')) {
            $data['sort_order'] = $this->input('sort_order');
        }

        if ($this->hasAny(['categories', 'categorie'])) {
            $data['categories'] = $this->input('categories', $this->input('categorie'));
        } elseif ($activeTab === 'general') {
            $data['categories'] = [];
        }

        if ($this->has('alt_text')) {
            $data['alt_text'] = $this->input('alt_text');
        }

        if ($this->has('target')) {
            $data['target'] = $this->input('target');
        }

        if ($this->has('delete_image')) {
            $data['delete_image'] = $this->boolean('delete_image');
        }

        if ($banner = $this->banner()) {
            $data = $this->preserveExistingTabValues($data, $banner, $activeTab);
        }

        $this->merge($data);
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preserveExistingTabValues(array $data, Banner $banner, string $activeTab): array
    {
        $metadata = $banner->metadata ?? [];
        $preserved = [
            'title' => $banner->title,
            'link_url' => $banner->link_url,
            'button_text' => $banner->button_text,
            'text' => $banner->text,
            'status' => $banner->status,
            'starts_at' => optional($banner->starts_at)->format('Y-m-d'),
            'ends_at' => optional($banner->ends_at)->format('Y-m-d'),
            'sort_order' => $banner->sort_order,
            'alt_text' => $metadata['alt_text'] ?? null,
            'target' => $metadata['target'] ?? '_self',
            'categories' => $banner->categories()->pluck('banner_categories.id')->all(),
        ];

        foreach ($preserved as $field => $value) {
            if (array_key_exists($field, $data) || ! $this->shouldPreserveField($field, $activeTab)) {
                continue;
            }

            $data[$field] = $value;
        }

        return $data;
    }

    private function shouldPreserveField(string $field, string $activeTab): bool
    {
        return match ($activeTab) {
            'image' => $field !== 'alt_text',
            'translations' => ! in_array($field, ['title', 'link_url', 'button_text', 'text'], true),
            default => in_array($field, ['title', 'link_url', 'button_text', 'text', 'alt_text'], true),
        };
    }
}
