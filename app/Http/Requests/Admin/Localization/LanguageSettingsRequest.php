<?php

namespace App\Http\Requests\Admin\Localization;

use Illuminate\Foundation\Http\FormRequest;

class LanguageSettingsRequest extends FormRequest
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
            'enabled_languages' => ['array'],
            'enabled_languages.*' => ['integer', 'exists:languages,id'],
            'default_language' => ['required', 'integer', 'exists:languages,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $enabledLanguages = (array) $this->input('enabled_languages', []);
        $defaultLanguage = $this->integer('default_language');

        if ($defaultLanguage > 0 && ! in_array($defaultLanguage, $enabledLanguages, false)) {
            $enabledLanguages[] = $defaultLanguage;
        }

        $this->merge([
            'enabled_languages' => array_values(array_unique(array_map('intval', $enabledLanguages))),
            'default_language' => $defaultLanguage,
        ]);
    }
}
