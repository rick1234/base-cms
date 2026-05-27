<?php

namespace App\Http\Requests\Admin\Banners;

use App\Models\Cms\BannerCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerCategoryRequest extends FormRequest
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
            'id' => ['nullable', 'integer', 'exists:banner_categories,id'],
            'parent_id' => ['nullable', 'integer', 'exists:banner_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('banner_categories', 'slug')->ignore($categoryId)],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'is_hidden_from_navigation' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'parent_id' => $this->input('parent_id', $this->input('parent')),
            'name' => $this->input('name', $this->input('naam')),
            'description' => $this->input('description', $this->input('omschrijving')),
            'status' => $this->normalizedStatus($this->input('status', 'active')),
            'is_hidden_from_navigation' => $this->boolean('is_hidden_from_navigation'),
        ]);
    }

    public function category(): ?BannerCategory
    {
        $id = $this->integer('id');

        return $id > 0 ? BannerCategory::query()->findOrFail($id) : null;
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
