<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\Cms\ContentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'parent_id' => $this->input('parent_id', $this->input('parent')),
            'name' => $this->input('name', $this->input('naam')),
            'description' => $this->input('description', $this->input('content')),
            'meta_description' => $this->input('meta_description', $this->input('metadescription')),
            'slider_category_id' => $this->input('slider_category_id', $this->input('slider')),
            'is_hidden_from_navigation' => $this->boolean('is_hidden_from_navigation') || $this->boolean('navigatiehoofdcategorie'),
            'status' => $this->normalizeStatus($this->input('status', $this->input('actief'))),
        ], fn (mixed $value): bool => $value !== null));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $category = $this->contentCategory();

        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:content_categories,id',
                Rule::notIn(array_filter([$category?->id])),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('content_categories', 'slug')->ignore($category?->id),
            ],
            'custom_url' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'slider_category_id' => ['nullable', 'integer', 'exists:slider_categories,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_hidden_from_navigation' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'image', 'max:20480'],
            'image_captions' => ['nullable', 'array'],
            'image_captions.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function contentCategory(): ?ContentCategory
    {
        $routeCategory = $this->route('contentCategory');

        if ($routeCategory instanceof ContentCategory) {
            return $routeCategory;
        }

        $id = $this->integer('id');

        return $id > 0 ? ContentCategory::query()->find($id) : null;
    }

    private function normalizeStatus(mixed $status): string
    {
        return match ((string) $status) {
            '0', 'false', 'offline', 'inactive' => 'inactive',
            default => 'active',
        };
    }
}
