<?php

namespace App\Http\Requests\Admin\Redirects;

use App\Models\Cms\CmsRedirect;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RedirectRequest extends FormRequest
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
        $redirectId = $this->integer('id') ?: null;

        return [
            'id' => ['nullable', 'integer', 'exists:redirects,id'],
            'source_path' => [
                'required',
                'string',
                'max:2048',
                Rule::unique('redirects', 'source_path')->ignore($redirectId),
            ],
            'target_url' => ['required', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
            'status_code' => ['required', 'integer', Rule::in(array_keys(CmsRedirect::statusCodes()))],
            'is_active' => ['boolean'],
            'preserve_query' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source_path' => CmsRedirect::normalizeSourcePath(
                $this->input('source_path', $this->input('old_link', $this->input('oldLink'))),
            ),
            'target_url' => CmsRedirect::normalizeTargetUrl(
                $this->input('target_url', $this->input('link')),
            ),
            'status_code' => (int) $this->input('status_code', 301),
            'is_active' => $this->boolean('is_active', $this->boolean('enabled', true)),
            'preserve_query' => $this->boolean('preserve_query'),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $targetUrl = (string) $this->input('target_url');

                if (! Str::startsWith($targetUrl, ['/', 'http://', 'https://'])) {
                    $validator->errors()->add('target_url', __('The target URL must be a site path or an HTTP(S) URL.'));
                }

                if ($this->isSelfReferencing($targetUrl)) {
                    $validator->errors()->add('target_url', __('A redirect cannot point to itself.'));
                }
            },
        ];
    }

    public function redirect(): ?CmsRedirect
    {
        $id = $this->integer('id');

        return $id > 0 ? CmsRedirect::query()->findOrFail($id) : null;
    }

    private function isSelfReferencing(string $targetUrl): bool
    {
        $sourcePath = CmsRedirect::normalizeSourcePath((string) $this->input('source_path'));

        if (! Str::startsWith($targetUrl, '/')) {
            return false;
        }

        return $sourcePath !== '' && $sourcePath === CmsRedirect::normalizeSourcePath($targetUrl);
    }
}
