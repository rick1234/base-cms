<?php

namespace App\Http\Requests\Admin\Events;

use Illuminate\Foundation\Http\FormRequest;

class EventActionRequest extends FormRequest
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
            'id' => ['nullable', 'integer'],
            'itemId' => ['nullable', 'integer', 'exists:events,id'],
            'item_id' => ['nullable', 'integer', 'exists:events,id'],
            'part_id' => ['nullable', 'integer', 'exists:event_parts,id'],
            'event_part_id' => ['nullable', 'integer', 'exists:event_parts,id'],
            'onderdeelId' => ['nullable', 'integer', 'exists:event_parts,id'],
        ];
    }
}
