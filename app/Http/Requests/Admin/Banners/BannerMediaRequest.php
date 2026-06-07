<?php

namespace App\Http\Requests\Admin\Banners;

use Illuminate\Foundation\Http\FormRequest;

class BannerMediaRequest extends FormRequest
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
            'id' => ['nullable', 'integer', 'exists:banners,id'],
            'bannerId' => ['nullable', 'integer', 'exists:banners,id'],
            'itemId' => ['nullable', 'integer', 'exists:banners,id'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:banner_categories,id'],
            'cat' => ['nullable', 'string'],
            'file' => ['nullable', 'image', 'max:10240'],
            'image' => ['nullable', 'image', 'max:10240'],
            'images' => ['array'],
            'images.*' => ['image', 'max:20480'],
            'banners' => ['array'],
            'banners.*' => ['image', 'max:10240'],
        ];
    }
}
