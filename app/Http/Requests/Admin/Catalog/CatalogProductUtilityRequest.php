<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class CatalogProductUtilityRequest extends FormRequest
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
            'id' => ['required', 'integer', 'exists:catalog_products,id'],
            'options' => ['array'],
            'options.*.id' => ['nullable', 'integer', 'exists:catalog_product_options,id'],
            'options.*.locale' => ['nullable', 'string', 'max:8'],
            'options.*.label' => ['nullable', 'string', 'max:255'],
            'options.*.value' => ['nullable', 'string'],
            'options.*.delete' => ['boolean'],
            'translations' => ['array'],
            'translations.*.id' => ['nullable', 'integer', 'exists:catalog_product_translations,id'],
            'translations.*.locale' => ['required_with:translations', 'string', 'max:8'],
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.subtitle' => ['nullable', 'string', 'max:255'],
            'translations.*.content' => ['nullable', 'string'],
            'translations.*.delete' => ['boolean'],
            'videos' => ['array'],
            'videos.*.id' => ['nullable', 'integer', 'exists:catalog_product_videos,id'],
            'videos.*.title' => ['nullable', 'string', 'max:255'],
            'videos.*.url' => ['nullable', 'url', 'max:255'],
            'videos.*.provider' => ['nullable', 'string', 'max:255'],
            'videos.*.delete' => ['boolean'],
            'stock' => ['array'],
            'stock.*.id' => ['nullable', 'integer', 'exists:catalog_stock,id'],
            'stock.*.location' => ['nullable', 'string', 'max:255'],
            'stock.*.quantity' => ['nullable', 'integer', 'min:0'],
            'stock.*.delete' => ['boolean'],
            'related_products' => ['array'],
            'related_products.*' => ['integer', 'exists:catalog_products,id'],
        ];
    }
}
