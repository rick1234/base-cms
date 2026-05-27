<?php

namespace App\Http\Requests;

use App\Support\Localization\TranslationRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LocaleSwitchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'max:16'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! app(TranslationRepository::class)->isEnabledLocale($this->locale())) {
                    $validator->errors()->add('locale', __('The selected language is not enabled.'));
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'locale' => $this->route('locale'),
        ]);
    }

    public function locale(): string
    {
        return app(TranslationRepository::class)->normalizeLocale($this->validated('locale'));
    }
}
