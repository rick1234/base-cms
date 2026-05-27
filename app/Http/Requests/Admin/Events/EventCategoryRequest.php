<?php

namespace App\Http\Requests\Admin\Events;

use App\Models\Cms\EventCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'parent_id' => $this->input('parent_id', $this->input('parent')),
            'name' => $this->input('name', $this->input('naam')),
            'description' => $this->input('description', $this->input('content')),
            'status' => $this->normalizeStatus($this->input('status', $this->input('actief'))),
        ], fn (mixed $value): bool => $value !== null));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $category = $this->eventCategory();

        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:event_categories,id',
                Rule::notIn(array_filter([$category?->id])),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('event_categories', 'slug')->ignore($category?->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function eventCategory(): ?EventCategory
    {
        $routeCategory = $this->route('eventCategory');

        if ($routeCategory instanceof EventCategory) {
            return $routeCategory;
        }

        $id = $this->integer('id');

        return $id > 0 ? EventCategory::query()->find($id) : null;
    }

    private function normalizeStatus(mixed $status): string
    {
        return match ((string) $status) {
            '0', 'false', 'offline', 'inactive' => 'inactive',
            default => 'active',
        };
    }
}
