<?php

namespace App\Http\Requests\Admin\Downloads;

use App\Models\Cms\Download;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DownloadRequest extends FormRequest
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
        $downloadId = $this->integer('id') ?: null;

        return [
            'id' => ['nullable', 'integer', 'exists:downloads,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'not_in:file', Rule::unique('downloads', 'slug')->ignore($downloadId)],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_password_protected' => ['boolean'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'link_expires_after_minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'unlimited_link' => ['boolean'],
            'active_tab' => ['sometimes', Rule::in(['general', 'storage', 'invites', 'log', 'qr'])],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:download_categories,id'],
            'file' => ['nullable', 'file', 'max:51200', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,gif,webp,zip,rar,7z'],
            'bestand' => ['nullable', 'file', 'max:51200', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,gif,webp,zip,rar,7z'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $unlimited = $this->boolean('unlimited_link');

        $name = $this->input('name', $this->input('naam'));
        $slug = $this->input('slug');

        $this->merge([
            'name' => $this->input('name', $this->input('naam')),
            'slug' => Str::slug((string) ($slug ?: $name)),
            'description' => $this->input('description', $this->input('omschrijving')),
            'status' => $this->normalizedStatus($this->input('status', $this->input('actief', 'active'))),
            'active_from' => $this->normalizedDate('active_from', 'startdatum'),
            'active_until' => $this->normalizedDate('active_until', 'einddatum'),
            'is_password_protected' => $this->boolean('is_password_protected'),
            'unlimited_link' => $unlimited,
            'link_expires_after_minutes' => $unlimited ? null : $this->input('link_expires_after_minutes'),
            'categories' => $this->input('categories', $this->input('categorie', [])),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->boolean('is_password_protected')) {
                    return;
                }

                $download = $this->download();

                if (blank($this->input('password')) && ! $download?->password_hash) {
                    $validator->errors()->add('password', __('A password is required when password protection is enabled.'));
                }
            },
        ];
    }

    public function download(): ?Download
    {
        $id = $this->integer('id');

        return $id > 0 ? Download::query()->findOrFail($id) : null;
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published' => 'active',
            '0', '2', '3', 'inactive', 'draft' => 'inactive',
            default => 'active',
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
