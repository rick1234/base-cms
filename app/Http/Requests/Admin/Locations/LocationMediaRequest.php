<?php

namespace App\Http\Requests\Admin\Locations;

use Illuminate\Foundation\Http\FormRequest;

class LocationMediaRequest extends FormRequest
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
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'uploadId' => ['nullable', 'integer', 'exists:location_images,id'],
            'uploadName' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'title_text' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'credit' => ['nullable', 'string', 'max:255'],
            'is_decorative' => ['nullable', 'boolean'],
            'sort_index' => ['nullable', 'string'],
            'file' => ['nullable', 'image', 'max:20480'],
            'image' => ['nullable', 'image', 'max:20480'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'max:20480'],
            'afbeelding' => ['nullable', 'image', 'max:20480'],
        ];
    }
}
