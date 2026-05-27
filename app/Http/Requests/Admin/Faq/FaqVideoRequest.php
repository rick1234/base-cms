<?php

namespace App\Http\Requests\Admin\Faq;

use Illuminate\Foundation\Http\FormRequest;

class FaqVideoRequest extends FormRequest
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
            'id' => ['required', 'integer', 'exists:faq_items,id'],
            'videos' => ['array'],
            'videos.*.id' => ['nullable', 'integer', 'exists:faq_videos,id'],
            'videos.*.title' => ['nullable', 'string', 'max:255'],
            'videos.*.url' => ['nullable', 'url', 'max:255'],
            'videos.*.provider' => ['nullable', 'string', 'max:255'],
            'videos.*.delete' => ['boolean'],
            'video' => ['array'],
            'video.*' => ['nullable', 'url', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('video') && ! $this->filled('videos')) {
            $videos = collect((array) $this->input('video'))
                ->map(fn (mixed $url): array => ['url' => $url])
                ->all();

            $this->merge(['videos' => $videos]);
        }
    }
}
