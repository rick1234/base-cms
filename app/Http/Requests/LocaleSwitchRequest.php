<?php

namespace App\Http\Requests;

use App\Models\Cms\Domain;
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
                if (! $this->isSelectableLocale($this->locale())) {
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

    private function isSelectableLocale(string $locale): bool
    {
        $translations = app(TranslationRepository::class);
        $domain = app()->bound('cms.active_domain') ? app('cms.active_domain') : null;

        if (! $domain instanceof Domain) {
            return $translations->isEnabledLocale($locale);
        }

        $activeLocales = $translations->activeArea() === 'admin'
            ? $domain->activeBackendLocales()
            : $domain->activeFrontendLocales();

        return $translations->isEnabledLocale($locale)
            && collect($activeLocales)
                ->map(fn (string $candidate): string => $translations->normalizeLocale($candidate))
                ->contains($translations->normalizeLocale($locale));
    }
}
