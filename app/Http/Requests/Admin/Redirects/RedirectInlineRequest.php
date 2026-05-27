<?php

namespace App\Http\Requests\Admin\Redirects;

use App\Models\Cms\CmsRedirect;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RedirectInlineRequest extends FormRequest
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
            'redirectid' => ['nullable', 'integer', 'exists:redirects,id'],
            'id' => ['nullable', 'integer', 'exists:redirects,id'],
            'newValue' => [
                'required',
                'string',
                'max:2048',
                Rule::unique('redirects', 'source_path')->ignore($this->redirectId()),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('newValue')) {
            $this->merge([
                'newValue' => CmsRedirect::normalizeSourcePath($this->input('newValue')),
            ]);
        }
    }

    public function redirectId(): int
    {
        return $this->integer('redirectid') ?: $this->integer('id');
    }
}
