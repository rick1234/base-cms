<?php

namespace App\Http\Requests\Admin\Events;

use App\Models\Cms\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'title' => $this->input('title', $this->input('titel')),
            'subtitle' => $this->input('subtitle', $this->input('subtitel')),
            'body' => $this->input('body', $this->input('content')),
            'meta_description' => $this->input('meta_description', $this->input('metadescription')),
            'status' => $this->normalizeStatus($this->input('status', $this->input('actief'))),
            'active_from' => $this->normalizeDate($this->input('active_from', $this->input('startdatum'))),
            'active_until' => $this->normalizeDate($this->input('active_until', $this->input('einddatum'))),
            'starts_at' => $this->normalizeDate($this->input('starts_at', $this->input('evenementstartdatum'))),
            'ends_at' => $this->normalizeDate($this->input('ends_at', $this->input('evenementeinddatum'))),
            'form_id' => $this->input('form_id', $this->input('formulier_id')),
            'categories' => $this->input('categories', $this->input('categorie')),
            'attachment_names' => $this->input('attachment_names', $this->input('attachmentNaam')),
            'existing_parts' => $this->input('existing_parts', $this->legacyExistingParts()),
            'new_parts' => $this->legacyParts(),
        ], fn (mixed $value): bool => $value !== null));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $event = $this->event();

        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('events', 'slug')->ignore($event?->id),
            ],
            'locale' => ['nullable', 'string', 'max:8'],
            'intro' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'form_id' => ['nullable', 'integer', 'exists:forms,id'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:event_categories,id'],
            'attachment_names' => ['nullable', 'array'],
            'attachment_names.*' => ['nullable', 'string', 'max:255'],
            'attachment_files' => ['nullable', 'array'],
            'attachment_files.*' => ['nullable', 'file', 'max:20480'],
            'existing_attachments' => ['nullable', 'array'],
            'existing_attachments.*.name' => ['nullable', 'string', 'max:255'],
            'existing_attachments.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'existing_attachments.*.delete' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:20480'],
            'image_caption' => ['nullable', 'string', 'max:255'],
            'existing_parts' => ['nullable', 'array'],
            'existing_parts.*.title' => ['nullable', 'string', 'max:255'],
            'existing_parts.*.date' => ['nullable', 'date'],
            'existing_parts.*.starts_at' => ['nullable', 'date_format:H:i'],
            'existing_parts.*.ends_at' => ['nullable', 'date_format:H:i'],
            'existing_parts.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'existing_parts.*.delete' => ['sometimes', 'boolean'],
            'new_parts' => ['nullable', 'array'],
            'new_parts.*.title' => ['nullable', 'string', 'max:255'],
            'new_parts.*.date' => ['nullable', 'date'],
            'new_parts.*.starts_at' => ['nullable', 'date_format:H:i'],
            'new_parts.*.ends_at' => ['nullable', 'date_format:H:i'],
            'new_parts.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'saveAndStay' => ['sometimes', 'boolean'],
        ];
    }

    public function event(): ?Event
    {
        $routeEvent = $this->route('event');

        if ($routeEvent instanceof Event) {
            return $routeEvent;
        }

        $id = $this->integer('id');

        return $id > 0 ? Event::query()->find($id) : null;
    }

    private function normalizeStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published' => 'published',
            '0', 'inactive', 'draft', '' => 'draft',
            'archived' => 'archived',
            default => is_string($status) ? $status : 'draft',
        };
    }

    private function normalizeDate(mixed $date): ?string
    {
        if (! is_string($date) || trim($date) === '') {
            return null;
        }

        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date) === 1) {
            return implode('-', array_reverse(explode('-', $date)));
        }

        return $date;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function legacyExistingParts(): ?array
    {
        $parts = [];

        foreach ($this->all() as $key => $value) {
            if (! is_string($key) || preg_match('/^naam_onderdeel_(\d+)$/', $key, $matches) !== 1) {
                continue;
            }

            $id = (int) $matches[1];
            $parts[$id] = [
                'title' => $value,
                'starts_at' => $this->input('tijdstipstart_onderdeel_'.$id),
                'ends_at' => $this->input('tijdstipeind_onderdeel_'.$id),
                'date' => $this->normalizeDate($this->input('datum_onderdeel_'.$id)),
            ];
        }

        return $parts === [] ? null : $parts;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function legacyParts(): ?array
    {
        $names = $this->input('onderdeel_naam');

        if (! is_array($names)) {
            return null;
        }

        $starts = $this->input('onderdeel_tijdstipstart', []);
        $ends = $this->input('onderdeel_tijdstipeind', []);
        $dates = $this->input('onderdeel_datum', []);

        return collect($names)
            ->map(fn (mixed $name, int|string $index): array => [
                'title' => $name,
                'starts_at' => $starts[$index] ?? null,
                'ends_at' => $ends[$index] ?? null,
                'date' => $this->normalizeDate($dates[$index] ?? null),
            ])
            ->all();
    }
}
