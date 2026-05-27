<?php

namespace App\Http\Requests\Admin\Locations;

use App\Models\Cms\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LocationRequest extends FormRequest
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
            'id' => ['nullable', 'integer', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:255'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'max:8'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'website_url' => ['nullable', 'string', 'max:255'],
            'chamber_of_commerce_number' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'latitude' => ['nullable', 'string', 'max:64'],
            'longitude' => ['nullable', 'string', 'max:64'],
            'map_info' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'slug' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:location_categories,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->input('name', $this->input('naam')),
            'street_address' => $this->input('street_address', $this->input('adres')),
            'postal_code' => $this->input('postal_code', $this->input('postcode')),
            'city' => $this->input('city', $this->input('plaats')),
            'country_code' => $this->input('country_code', $this->input('landcode')),
            'phone' => $this->input('phone', $this->input('telefoon')),
            'website_url' => $this->input('website_url', $this->input('url')),
            'chamber_of_commerce_number' => $this->input('chamber_of_commerce_number', $this->input('kvknummer')),
            'description' => $this->input('description', $this->input('omschrijving')),
            'latitude' => $this->input('latitude', $this->input('google_maps_coordinaat_x')),
            'longitude' => $this->input('longitude', $this->input('google_maps_coordinaat_y')),
            'map_info' => $this->input('map_info', $this->input('google_maps_info')),
            'status' => $this->normalizedStatus($this->input('status', 'active')),
            'active_from' => $this->normalizedDate('active_from', 'startdatum'),
            'active_until' => $this->normalizedDate('active_until', 'einddatum'),
            'categories' => $this->input('categories', $this->input('categorie', [])),
        ]);
    }

    public function location(): ?Location
    {
        $id = $this->integer('id');

        return $id > 0 ? Location::query()->findOrFail($id) : null;
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published' => 'active',
            '0', '2', '3', 'inactive', 'draft' => 'inactive',
            default => 'active',
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
}
