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
            'uploadName' => ['nullable', 'string', 'max:255'],
            'sort_index' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:8192'],
            'file' => ['nullable', 'image', 'max:8192'],
        ];
    }
}
