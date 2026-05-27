<?php

namespace App\Http\Requests\Admin\Localization;

use App\Models\Cms\CmsLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LanguageRequest extends FormRequest
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
        $languageId = $this->integer('id') ?: null;

        return [
            'id' => ['nullable', 'integer', 'exists:languages,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:16', Rule::unique('languages', 'code')->ignore($languageId)],
            'native_name' => ['nullable', 'string', 'max:255'],
            'direction' => ['required', 'string', 'in:ltr,rtl'],
            'fallback_locale' => ['nullable', 'string', 'max:16'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'is_enabled' => ['boolean'],
            'is_default' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name', $this->input('naam'));
        $code = Str::of((string) $this->input('code'))->replace('_', '-')->toString();
        $isDefault = $this->boolean('is_default');

        $this->merge([
            'name' => $name,
            'slug' => Str::slug((string) $this->input('slug', $name)),
            'code' => $code,
            'native_name' => $this->input('native_name') ?: null,
            'direction' => $this->input('direction') ?: CmsLanguage::directionFor($code),
            'fallback_locale' => $this->input('fallback_locale') ?: null,
            'description' => $this->input('description', $this->input('omschrijving')),
            'status' => $this->normalizedStatus($this->input('status', $this->input('active', 'active'))),
            'is_enabled' => $isDefault || $this->boolean('is_enabled'),
            'is_default' => $isDefault,
        ]);
    }

    public function language(): ?CmsLanguage
    {
        $id = $this->integer('id');

        return $id > 0 ? CmsLanguage::query()->findOrFail($id) : null;
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published' => 'active',
            '0', '2', '3', 'inactive', 'draft' => 'inactive',
            default => 'active',
        };
    }
}
