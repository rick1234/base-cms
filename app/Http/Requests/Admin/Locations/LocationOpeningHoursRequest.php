<?php

namespace App\Http\Requests\Admin\Locations;

use App\Models\Cms\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LocationOpeningHoursRequest extends FormRequest
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
            'id' => ['required', 'integer', 'exists:locations,id'],
            'opening_hours' => ['array'],
            'opening_hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'opening_hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'opening_hours.*.is_closed' => ['boolean'],
            'special_opening_hours' => ['array'],
            'special_opening_hours.*.id' => ['nullable', 'integer', 'exists:location_special_opening_hours,id'],
            'special_opening_hours.*.title' => ['nullable', 'string', 'max:255'],
            'special_opening_hours.*.date' => ['nullable', 'date'],
            'special_opening_hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'special_opening_hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'special_opening_hours.*.is_closed' => ['boolean'],
            'special_opening_hours.*.delete' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $openingHours = (array) $this->input('opening_hours', []);

        foreach (array_keys(Location::dayNames()) as $day) {
            $openingHours[$day] ??= [
                'opens_at' => $this->timeValue($this->input('openingtime'.$day)),
                'closes_at' => $this->timeValue($this->input('closingtime'.$day)),
                'is_closed' => $this->boolean('closed'.$day),
            ];
        }

        $specialOpeningHours = (array) $this->input('special_opening_hours', []);

        foreach ((array) $this->input('specialeDagTitel', []) as $index => $title) {
            $specialOpeningHours[] = [
                'title' => $title,
                'date' => $this->dateValue($this->input("specialeDagDatum.$index")),
                'opens_at' => $this->timeValue($this->input("specialeDagOpeningTime.$index")),
                'closes_at' => $this->timeValue($this->input("specialeDagClosingTime.$index")),
                'is_closed' => (bool) $this->input("specialeDagClosed.$index"),
            ];
        }

        $this->merge([
            'opening_hours' => $openingHours,
            'special_opening_hours' => $specialOpeningHours,
        ]);
    }

    public function location(): Location
    {
        return Location::query()->findOrFail($this->integer('id'));
    }

    private function timeValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return substr((string) $value, 0, 5);
    }

    private function dateValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', (string) $value) === 1) {
            return Str::of((string) $value)->explode('-')->reverse()->join('-');
        }

        return (string) $value;
    }
}
