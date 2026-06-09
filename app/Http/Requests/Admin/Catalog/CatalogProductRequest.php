<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Models\Cms\CatalogProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CatalogProductRequest extends FormRequest
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
        $productId = $this->integer('id') ?: null;

        return [
            'id' => ['nullable', 'integer', 'exists:catalog_products,id'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('catalog_products', 'sku')->ignore($productId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'brand_id' => ['nullable', 'integer', 'exists:catalog_brands,id'],
            'status' => ['required', 'string', 'in:published,draft,archived'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:catalog_categories,id'],
            'attachment_names' => ['array'],
            'attachment_names.*' => ['nullable', 'string', 'max:255'],
            'attachment_files' => ['array'],
            'attachment_files.*' => ['file', 'max:10240'],
            'existing_attachments' => ['array'],
            'existing_attachments.*.name' => ['nullable', 'string', 'max:255'],
            'existing_attachments.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'existing_attachments.*.delete' => ['boolean'],
            'active_tab' => ['sometimes', Rule::in(['edit', 'seo'])],
            'saveAndStay' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $activeTab = $this->input('active_tab', 'edit');
        $data = [
            'active_tab' => $activeTab,
            'sku' => $this->input('sku', $this->input('artikelnummer')),
            'name' => $this->input('name', $this->input('naam')),
            'description' => $this->input('description', $this->input('omschrijving')),
            'price' => $this->normalizedMoney('price', 'prijs'),
            'meta_title' => $this->input('meta_title'),
            'meta_description' => $this->input('meta_description', $this->input('metadescription')),
            'brand_id' => $this->input('brand_id', $this->input('merk_id')),
            'status' => $this->normalizedStatus($this->input('status', 'published')),
            'active_from' => $this->normalizedDate('active_from', 'startdatum'),
            'active_until' => $this->normalizedDate('active_until', 'einddatum'),
            'categories' => $this->input('categories', $this->input('categorie', [])),
            'attachment_names' => $this->input('attachment_names', $this->input('attachmentNaam', [])),
        ];

        if ($product = $this->product()) {
            $data = $this->preserveExistingTabValues($data, $product, $activeTab);
        }

        $this->merge($data);
    }

    public function product(): ?CatalogProduct
    {
        $id = $this->integer('id');

        return $id > 0 ? CatalogProduct::query()->findOrFail($id) : null;
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published' => 'published',
            '2', '0', 'inactive', 'draft' => 'draft',
            'archived' => 'archived',
            default => 'published',
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

    private function normalizedMoney(string $key, string $legacyKey): string
    {
        $value = $this->input($key, $this->input($legacyKey, 0));

        if (blank($value)) {
            return '';
        }

        return str_replace(',', '.', (string) $value);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preserveExistingTabValues(array $data, CatalogProduct $product, string $activeTab): array
    {
        $preserved = [
            'sku' => $product->sku,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->priceForInput(),
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'brand_id' => $product->brand_id,
            'status' => $product->status,
            'active_from' => optional($product->active_from)->format('Y-m-d'),
            'active_until' => optional($product->active_until)->format('Y-m-d'),
            'categories' => $product->categories()->pluck('catalog_categories.id')->all(),
        ];

        foreach ($preserved as $field => $value) {
            if ($this->shouldPreserveField($field, $activeTab)) {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    private function shouldPreserveField(string $field, string $activeTab): bool
    {
        $seoFields = ['meta_title', 'meta_description'];

        return match ($activeTab) {
            'seo' => ! in_array($field, $seoFields, true),
            default => in_array($field, $seoFields, true),
        };
    }
}
