<?php

namespace App\Http\Requests\Admin\Events;

use Illuminate\Foundation\Http\FormRequest;

class EventMediaRequest extends FormRequest
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
            'uploadId' => ['nullable', 'integer'],
            'uploadName' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'title_text' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'credit' => ['nullable', 'string', 'max:255'],
            'is_decorative' => ['sometimes', 'boolean'],
            'sort_index' => ['nullable', 'string', 'max:1000'],
            'file' => ['nullable', 'image', 'max:20480'],
            'image' => ['nullable', 'image', 'max:20480'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'max:20480'],
        ];
    }
}
