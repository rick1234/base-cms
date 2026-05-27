<?php

namespace App\Http\Requests\Admin\Downloads;

use App\Models\Cms\DownloadCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DownloadCategoryRequest extends FormRequest
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
        $categoryId = $this->integer('id') ?: null;

        return [
            'id' => ['nullable', 'integer', 'exists:download_categories,id'],
            'parent_id' => ['nullable', 'integer', 'exists:download_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('download_categories', 'slug')->ignore($categoryId)],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_hidden_from_navigation' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name', $this->input('naam'));
        $slug = $this->input('slug');

        $this->merge([
            'parent_id' => $this->input('parent_id', $this->input('parent')),
            'name' => $name,
            'slug' => Str::slug((string) ($slug ?: $name)),
            'description' => $this->input('description', $this->input('omschrijving')),
            'status' => $this->normalizedStatus($this->input('status', $this->input('actief', 'active'))),
            'is_hidden_from_navigation' => $this->boolean('is_hidden_from_navigation', $this->boolean('navigatieHidden')),
        ]);
    }

    public function category(): ?DownloadCategory
    {
        $id = $this->integer('id');

        return $id > 0 ? DownloadCategory::query()->findOrFail($id) : null;
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
