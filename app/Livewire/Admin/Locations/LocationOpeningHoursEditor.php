<?php

namespace App\Livewire\Admin\Locations;

use App\Actions\Admin\Locations\SaveLocationOpeningHours;
use App\Models\Cms\Location;
use App\Models\Cms\LocationOpeningHour;
use App\Models\Cms\LocationSpecialOpeningHour;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class LocationOpeningHoursEditor extends Component
{
    public Location $location;

    /**
     * @var array<string, string>
     */
    public array $dayNames = [];

    /**
     * @var array<string, array{opens_at: ?string, closes_at: ?string, is_closed: bool}>
     */
    public array $openingHours = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $specialOpeningHours = [];

    public ?string $message = null;

    public string $messageLevel = 'success';

    public int $nextTemporaryId = -1;

    public function mount(Location $location): void
    {
        $this->ensureAuthorized();

        $this->location = $location;
        $this->dayNames = Location::dayNames();
        $this->loadOpeningHours();
    }

    public function save(SaveLocationOpeningHours $save): void
    {
        $this->ensureAuthorized();

        $data = Validator::make([
            'openingHours' => $this->openingHours,
            'specialOpeningHours' => $this->specialOpeningHours,
        ], [
            'openingHours' => ['array'],
            'openingHours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'openingHours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'openingHours.*.is_closed' => ['boolean'],
            'specialOpeningHours' => ['array'],
            'specialOpeningHours.*.id' => ['nullable', 'integer', 'exists:location_special_opening_hours,id'],
            'specialOpeningHours.*.title' => ['nullable', 'string', 'max:255'],
            'specialOpeningHours.*.date' => ['nullable', 'date'],
            'specialOpeningHours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'specialOpeningHours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'specialOpeningHours.*.is_closed' => ['boolean'],
            'specialOpeningHours.*.delete' => ['boolean'],
        ])->validate();

        $this->location = $save->handle($this->location, [
            'opening_hours' => $data['openingHours'],
            'special_opening_hours' => $data['specialOpeningHours'],
        ], auth()->user());
        $this->loadOpeningHours();
        $this->messageLevel = 'success';
        $this->message = __('Opening hours saved.');
    }

    public function toggleDayClosed(string $day): void
    {
        if (! isset($this->openingHours[$day])) {
            return;
        }

        $this->openingHours[$day]['is_closed'] = ! (bool) ($this->openingHours[$day]['is_closed'] ?? false);
        $this->message = null;
    }

    public function addSpecialOpeningHour(): void
    {
        $this->specialOpeningHours[] = $this->blankSpecialOpeningHour();
        $this->message = null;
    }

    public function removeSpecialOpeningHour(int $index): void
    {
        if (! isset($this->specialOpeningHours[$index])) {
            return;
        }

        if (! empty($this->specialOpeningHours[$index]['id'])) {
            $this->specialOpeningHours[$index]['delete'] = true;
        } else {
            array_splice($this->specialOpeningHours, $index, 1);
        }

        $this->message = null;
    }

    public function restoreSpecialOpeningHour(int $index): void
    {
        if (! isset($this->specialOpeningHours[$index])) {
            return;
        }

        $this->specialOpeningHours[$index]['delete'] = false;
        $this->message = null;
    }

    public function toggleSpecialClosed(int $index): void
    {
        if (! isset($this->specialOpeningHours[$index])) {
            return;
        }

        $this->specialOpeningHours[$index]['is_closed'] = ! (bool) ($this->specialOpeningHours[$index]['is_closed'] ?? false);
        $this->message = null;
    }

    public function render(): View
    {
        return view('livewire.admin.locations.location-opening-hours-editor');
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);
    }

    private function loadOpeningHours(): void
    {
        $this->location->load(['openingHours', 'specialOpeningHours']);
        $storedOpeningHours = $this->location->openingHours->keyBy('day');

        $this->openingHours = collect($this->dayNames)
            ->mapWithKeys(fn (string $label, string $day): array => [
                $day => $this->openingHourToArray($storedOpeningHours->get($day)),
            ])
            ->all();

        $this->specialOpeningHours = $this->location->specialOpeningHours
            ->values()
            ->map(fn (LocationSpecialOpeningHour $openingHour): array => $this->specialOpeningHourToArray($openingHour))
            ->all();
    }

    /**
     * @return array{opens_at: ?string, closes_at: ?string, is_closed: bool}
     */
    private function openingHourToArray(?LocationOpeningHour $openingHour): array
    {
        return [
            'opens_at' => $this->timeValue($openingHour?->opens_at),
            'closes_at' => $this->timeValue($openingHour?->closes_at),
            'is_closed' => (bool) ($openingHour?->is_closed ?? false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function specialOpeningHourToArray(LocationSpecialOpeningHour $openingHour): array
    {
        return [
            'state_id' => $openingHour->id,
            'id' => $openingHour->id,
            'title' => $openingHour->title,
            'date' => optional($openingHour->date)->format('Y-m-d'),
            'opens_at' => $this->timeValue($openingHour->opens_at),
            'closes_at' => $this->timeValue($openingHour->closes_at),
            'is_closed' => (bool) $openingHour->is_closed,
            'delete' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankSpecialOpeningHour(): array
    {
        return [
            'state_id' => $this->nextTemporaryId--,
            'id' => null,
            'title' => null,
            'date' => null,
            'opens_at' => null,
            'closes_at' => null,
            'is_closed' => false,
            'delete' => false,
        ];
    }

    private function timeValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return substr((string) $value, 0, 5);
    }
}
