<?php

namespace App\Http\Requests\Admin\Content;

use Illuminate\Foundation\Http\FormRequest;

class ContentActionRequest extends FormRequest
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
            'itemId' => ['nullable', 'integer', 'exists:content_items,id'],
            'item_id' => ['nullable', 'integer', 'exists:content_items,id'],
        ];
    }
}
