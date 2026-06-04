<?php

namespace App\Http\Requests\Admin\Locations;

use App\Models\Cms\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:location_categories,id'],
            'categories_present' => ['sometimes', 'boolean'],
            'active_tab' => ['sometimes', Rule::in(['general', 'location'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $activeTab = $this->string('active_tab')->toString() ?: 'general';
        $data = ['active_tab' => $activeTab];

        if ($this->hasAny(['name', 'naam'])) {
            $data['name'] = $this->input('name', $this->input('naam'));
        }

        if ($this->hasAny(['street_address', 'adres'])) {
            $data['street_address'] = $this->input('street_address', $this->input('adres'));
        }

        if ($this->hasAny(['postal_code', 'postcode'])) {
            $data['postal_code'] = $this->input('postal_code', $this->input('postcode'));
        }

        if ($this->hasAny(['city', 'plaats'])) {
            $data['city'] = $this->input('city', $this->input('plaats'));
        }

        if ($this->hasAny(['country_code', 'landcode'])) {
            $data['country_code'] = $this->input('country_code', $this->input('landcode'));
        }

        if ($this->has('email')) {
            $data['email'] = $this->input('email');
        }

        if ($this->hasAny(['phone', 'telefoon'])) {
            $data['phone'] = $this->input('phone', $this->input('telefoon'));
        }

        if ($this->hasAny(['website_url', 'url'])) {
            $data['website_url'] = $this->input('website_url', $this->input('url'));
        }

        if ($this->hasAny(['chamber_of_commerce_number', 'kvknummer'])) {
            $data['chamber_of_commerce_number'] = $this->input('chamber_of_commerce_number', $this->input('kvknummer'));
        }

        if ($this->hasAny(['description', 'omschrijving'])) {
            $data['description'] = $this->input('description', $this->input('omschrijving'));
        }

        if ($this->hasAny(['latitude', 'google_maps_coordinaat_x'])) {
            $data['latitude'] = $this->input('latitude', $this->input('google_maps_coordinaat_x'));
        }

        if ($this->hasAny(['longitude', 'google_maps_coordinaat_y'])) {
            $data['longitude'] = $this->input('longitude', $this->input('google_maps_coordinaat_y'));
        }

        if ($this->hasAny(['map_info', 'google_maps_info'])) {
            $data['map_info'] = $this->input('map_info', $this->input('google_maps_info'));
        }

        if ($this->has('status')) {
            $data['status'] = $this->normalizedStatus($this->input('status'));
        }

        if ($this->hasAny(['active_from', 'startdatum'])) {
            $data['active_from'] = $this->normalizedDate('active_from', 'startdatum');
        }

        if ($this->hasAny(['active_until', 'einddatum'])) {
            $data['active_until'] = $this->normalizedDate('active_until', 'einddatum');
        }

        if ($this->hasAny(['categories', 'categorie']) || $this->has('categories_present')) {
            $data['categories'] = $this->input('categories', $this->input('categorie', []));
        }

        $location = $this->location();

        if ($location) {
            $data = $this->preserveExistingTabValues($data, $location, $activeTab);
        } elseif (! array_key_exists('status', $data)) {
            $data['status'] = 'active';
        }

        $this->merge($data);
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preserveExistingTabValues(array $data, Location $location, string $activeTab): array
    {
        $preserved = [
            'name' => $location->name,
            'street_address' => $location->street_address,
            'postal_code' => $location->postal_code,
            'city' => $location->city,
            'country_code' => $location->country_code,
            'email' => $location->email,
            'phone' => $location->phone,
            'website_url' => $location->website_url,
            'chamber_of_commerce_number' => $location->chamber_of_commerce_number,
            'description' => $location->description,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'map_info' => $location->map_info,
            'status' => $location->status,
            'active_from' => optional($location->active_from)->format('Y-m-d'),
            'active_until' => optional($location->active_until)->format('Y-m-d'),
            'categories' => $location->categories()->pluck('location_categories.id')->all(),
        ];

        foreach ($preserved as $field => $value) {
            if (array_key_exists($field, $data) || ! $this->shouldPreserveField($field, $activeTab)) {
                continue;
            }

            $data[$field] = $value;
        }

        return $data;
    }

    private function shouldPreserveField(string $field, string $activeTab): bool
    {
        return match ($activeTab) {
            'location' => ! in_array($field, ['latitude', 'longitude', 'map_info'], true),
            default => in_array($field, ['latitude', 'longitude', 'map_info'], true),
        };
    }
}
