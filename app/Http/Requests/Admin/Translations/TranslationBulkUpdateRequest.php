<?php

namespace App\Http\Requests\Admin\Translations;

use App\Support\Localization\TranslationRepository;
use Illuminate\Foundation\Http\FormRequest;

class TranslationBulkUpdateRequest extends FormRequest
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
            'locale' => ['required', 'string', 'max:16'],
            'translations' => ['array'],
            'translations.*' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'locale' => app(TranslationRepository::class)->normalizeLocale($this->input('locale')),
            'translations' => $this->input('translations', []),
        ]);
    }
}
