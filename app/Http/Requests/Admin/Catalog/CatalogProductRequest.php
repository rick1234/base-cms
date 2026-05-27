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
            'price_note' => ['nullable', 'string'],
            'is_on_sale' => ['boolean'],
            'sale_starts_at' => ['nullable', 'date'],
            'sale_ends_at' => ['nullable', 'date', 'after_or_equal:sale_starts_at'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price_note' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'brand_id' => ['nullable', 'integer', 'exists:catalog_brands,id'],
            'promotion_id' => ['nullable', 'integer', 'exists:catalog_promotions,id'],
            'can_be_engraved' => ['boolean'],
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => $this->input('sku', $this->input('artikelnummer')),
            'name' => $this->input('name', $this->input('naam')),
            'description' => $this->input('description', $this->input('omschrijving')),
            'price' => $this->normalizedMoney('price', 'prijs'),
            'price_note' => $this->input('price_note', $this->input('prijsopmerking')),
            'is_on_sale' => $this->normalizedBoolean('is_on_sale', 'actie', false),
            'sale_starts_at' => $this->normalizedDate('sale_starts_at', 'actiestartdatum'),
            'sale_ends_at' => $this->normalizedDate('sale_ends_at', 'actieeinddatum'),
            'sale_price' => $this->normalizedMoney('sale_price', 'actieprijs'),
            'sale_price_note' => $this->input('sale_price_note', $this->input('actieprijsopmerking')),
            'meta_description' => $this->input('meta_description', $this->input('metadescription')),
            'brand_id' => $this->input('brand_id', $this->input('merk_id')),
            'promotion_id' => $this->input('promotion_id', $this->input('promotie_id')),
            'can_be_engraved' => $this->normalizedBoolean('can_be_engraved', 'graveren', false),
            'status' => $this->normalizedStatus($this->input('status', 'published')),
            'active_from' => $this->normalizedDate('active_from', 'startdatum'),
            'active_until' => $this->normalizedDate('active_until', 'einddatum'),
            'categories' => $this->input('categories', $this->input('categorie', [])),
            'attachment_names' => $this->input('attachment_names', $this->input('attachmentNaam', [])),
        ]);
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

    private function normalizedBoolean(string $key, ?string $legacyKey, bool $default): bool
    {
        if ($this->has($key)) {
            return $this->boolean($key);
        }

        if ($legacyKey && $this->has($legacyKey)) {
            return $this->boolean($legacyKey);
        }

        return $default;
    }
}
