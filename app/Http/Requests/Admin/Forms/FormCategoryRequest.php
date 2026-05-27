<?php

namespace App\Http\Requests\Admin\Forms;

use App\Models\Cms\FormCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FormCategoryRequest extends FormRequest
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
            'id' => ['nullable', 'integer', 'exists:form_categories,id'],
            'parent_id' => ['nullable', 'integer', 'exists:form_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('form_categories', 'slug')->ignore($categoryId)],
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
            'is_hidden_from_navigation' => $this->normalizedBoolean('is_hidden_from_navigation', false),
        ]);
    }

    public function category(): ?FormCategory
    {
        $id = $this->integer('id');

        return $id > 0 ? FormCategory::query()->findOrFail($id) : null;
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published' => 'active',
            '0', '2', '3', 'inactive', 'draft' => 'inactive',
            default => 'active',
        };
    }

    private function normalizedBoolean(string $key, bool $default): bool
    {
        $value = $this->input($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return Str::of($value)->lower()->is(['1', 'true', 'yes', 'ja', 'on']);
        }

        return (bool) $value;
    }
}
