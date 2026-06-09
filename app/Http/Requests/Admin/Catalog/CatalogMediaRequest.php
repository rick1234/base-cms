<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class CatalogMediaRequest extends FormRequest
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
            'id' => ['nullable', 'integer'],
            'uploadId' => ['nullable', 'integer'],
            'catalog_product_id' => ['nullable', 'integer', 'exists:catalog_products,id'],
            'caption' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'title_text' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'credit' => ['nullable', 'string', 'max:255'],
            'is_decorative' => ['boolean'],
            'uploadName' => ['nullable', 'string', 'max:255'],
            'sort_index' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:8192'],
            'file' => ['nullable', 'image', 'max:8192'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:8192'],
        ];
    }
}
