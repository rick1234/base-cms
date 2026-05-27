<?php

namespace App\Http\Requests\Admin\Translations;

use App\Models\Cms\TranslationKey;
use App\Support\Localization\TranslationRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TranslationKeyRequest extends FormRequest
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
        $translationKeyId = $this->integer('id') ?: null;

        return [
            'id' => ['nullable', 'integer', 'exists:translation_keys,id'],
            'area' => ['required', Rule::in(['admin', 'frontend', 'shared'])],
            'group' => ['required', 'string', 'max:120'],
            'key' => [
                'required',
                'string',
                'max:500',
                Rule::unique('translation_keys', 'key')
                    ->where('area', $this->input('area'))
                    ->where('group', $this->input('group'))
                    ->ignore($translationKeyId),
            ],
            'source_locale' => ['required', 'string', 'max:16'],
            'source_text' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_system' => ['boolean'],
            'values' => ['array'],
            'values.*.value' => ['nullable', 'string'],
            'values.*.is_reviewed' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $translations = app(TranslationRepository::class);

        $this->merge([
            'area' => $this->input('area', 'shared'),
            'group' => $this->input('group') ?: '*',
            'source_locale' => $translations->normalizeLocale($this->input('source_locale') ?: $translations->sourceLocale()),
            'status' => $this->normalizedStatus($this->input('status', 'active')),
            'is_system' => $this->boolean('is_system'),
            'values' => $this->normalizedValues(),
        ]);
    }

    public function translationKey(): ?TranslationKey
    {
        $id = $this->integer('id');

        return $id > 0 ? TranslationKey::query()->findOrFail($id) : null;
    }

    /**
     * @return array<string, array{value: ?string, is_reviewed: bool}>
     */
    private function normalizedValues(): array
    {
        $values = $this->input('values', []);

        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->mapWithKeys(fn (mixed $value, string|int $locale): array => [
                app(TranslationRepository::class)->normalizeLocale((string) $locale) => [
                    'value' => is_array($value) ? ($value['value'] ?? null) : $value,
                    'is_reviewed' => is_array($value) && filter_var($value['is_reviewed'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ],
            ])
            ->all();
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '0', 'inactive', 'draft' => 'inactive',
            default => 'active',
        };
    }
}
