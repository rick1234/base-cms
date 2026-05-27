<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Models\Cms\CatalogCoupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CatalogCouponRequest extends FormRequest
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
        $couponId = $this->integer('id') ?: null;

        return [
            'id' => ['nullable', 'integer', 'exists:catalog_coupons,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('catalog_coupons', 'code')->ignore($couponId)],
            'percentage_discount' => ['required', 'integer', 'min:0', 'max:100'],
            'minimum_amount' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
            'usage_mode' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->input('name', $this->input('naam')),
            'percentage_discount' => $this->input('percentage_discount', $this->input('kortingspercentage', 0)),
            'minimum_amount' => $this->normalizedMoney('minimum_amount', 'minimum_bedrag'),
            'starts_at' => $this->normalizedDate('starts_at', 'startdatum'),
            'ends_at' => $this->normalizedDate('ends_at', 'einddatum'),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : $this->boolean('aktief', true),
            'usage_mode' => $this->input('usage_mode', 'any'),
        ]);
    }

    public function coupon(): ?CatalogCoupon
    {
        $id = $this->integer('id');

        return $id > 0 ? CatalogCoupon::query()->findOrFail($id) : null;
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
        return str_replace(',', '.', (string) $this->input($key, $this->input($legacyKey, 0)));
    }
}
