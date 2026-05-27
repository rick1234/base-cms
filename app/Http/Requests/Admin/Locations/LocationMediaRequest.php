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
            'sort_index' => ['nullable', 'string'],
            'file' => ['nullable', 'image', 'max:10240'],
            'image' => ['nullable', 'image', 'max:10240'],
            'afbeelding' => ['nullable', 'image', 'max:10240'],
        ];
    }
}
