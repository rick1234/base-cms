<?php

namespace App\Http\Requests\Admin\Localization;

use App\Models\Cms\Country;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CountryRequest extends FormRequest
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
        $countryId = $this->integer('id') ?: null;

        return [
            'id' => ['nullable', 'integer', 'exists:countries,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'is_enabled' => ['boolean'],
            'iso2' => ['required', 'string', 'size:2', Rule::unique('countries', 'iso2')->ignore($countryId)],
            'iso3' => ['nullable', 'string', 'size:3'],
            'numeric_code' => ['nullable', 'string', 'regex:/^[0-9]{3}$/'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'region_code' => ['nullable', 'string', Rule::in(array_keys($this->regionOptions()))],
            'charges_vat' => ['boolean'],
            'shipping_general_cents' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'shipping_envelope_cents' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'shipping_small_box_cents' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'shipping_big_box_cents' => ['nullable', 'integer', 'min:0', 'max:99999999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name', $this->input('naam'));
        $iso2 = $this->input('iso2', $this->input('code'));
        $numericCode = $this->input('numeric_code');

        if (is_numeric($numericCode)) {
            $numericCode = str_pad((string) $numericCode, 3, '0', STR_PAD_LEFT);
        }

        $this->merge([
            'name' => $name,
            'slug' => Str::slug((string) $this->input('slug', $name)),
            'description' => $this->input('description', $this->input('omschrijving')),
            'status' => $this->normalizedStatus($this->input('status', $this->input('active', 'active'))),
            'is_enabled' => $this->boolean('is_enabled', true),
            'iso2' => Str::upper((string) $iso2),
            'iso3' => $this->filled('iso3') ? Str::upper((string) $this->input('iso3')) : null,
            'numeric_code' => $numericCode ?: null,
            'currency_code' => $this->filled('currency_code') ? Str::upper((string) $this->input('currency_code')) : null,
            'region_code' => $this->input('region_code', $this->input('regio')) ?: null,
            'charges_vat' => $this->boolean('charges_vat', $this->boolean('vat')),
            'shipping_general_cents' => $this->input('shipping_general_cents', $this->input('algemeen')) ?: null,
            'shipping_envelope_cents' => $this->input('shipping_envelope_cents', $this->input('envelope')) ?: null,
            'shipping_small_box_cents' => $this->input('shipping_small_box_cents', $this->input('smallbox')) ?: null,
            'shipping_big_box_cents' => $this->input('shipping_big_box_cents', $this->input('bigbox')) ?: null,
        ]);
    }

    public function country(): ?Country
    {
        $id = $this->integer('id');

        return $id > 0 ? Country::query()->findOrFail($id) : null;
    }

    /**
     * @return array<string, string>
     */
    public function regionOptions(): array
    {
        return [
            'EU' => __('Europe'),
            'AF' => __('Africa'),
            'AN' => __('Antarctica'),
            'AS' => __('Asia'),
            'NA' => __('North America'),
            'SA' => __('South America'),
            'OC' => __('Oceania'),
        ];
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
