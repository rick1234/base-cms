<?php

namespace App\Http\Requests\Admin;

use App\Models\Cms\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $page = $this->route('page');

        if ($page instanceof Page) {
            return $this->user()?->can('update', $page) ?? false;
        }

        return $this->user()?->can('create', Page::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'domain_id' => ['nullable', 'integer', 'exists:domains,id'],
            'parent_id' => ['nullable', 'integer', 'exists:cms_pages,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::notIn(config('cms.reserved_slugs')),
            ],
            'navigation_label' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'template' => ['required', 'string', 'max:80'],
            'status' => ['required', Rule::in(config('cms.page_statuses'))],
            'sort_order' => ['required', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('domain_id') === '') {
            $this->merge(['domain_id' => null]);
        }
    }
}
