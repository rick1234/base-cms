<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\Cms\ContentItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContentItemRequest extends FormRequest
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
            'locale' => $this->input('locale', $this->input('taalcode')),
            'active_from' => $this->normalizeDate($this->input('active_from', $this->input('startdatum'))),
            'active_until' => $this->normalizeDate($this->input('active_until', $this->input('einddatum'))),
            'form_id' => $this->input('form_id', $this->input('formulier_id')),
            'slider_category_id' => $this->input('slider_category_id', $this->input('slider')),
            'categories' => $this->input('categories', $this->input('categorie')),
            'status' => $this->normalizeStatus($this->input('status')),
        ], fn (mixed $value): bool => $value !== null));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $contentItem = $this->contentItem();

        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('content_items', 'slug')->ignore($contentItem?->id),
            ],
            'locale' => ['nullable', 'string', 'max:8'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
            'form_id' => ['nullable', 'integer', 'exists:forms,id'],
            'slider_category_id' => ['nullable', 'integer', 'exists:slider_categories,id'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:content_categories,id'],
            'attachment_names' => ['nullable', 'array'],
            'attachment_names.*' => ['nullable', 'string', 'max:255'],
            'attachment_files' => ['nullable', 'array'],
            'attachment_files.*' => ['nullable', 'file', 'max:20480'],
            'existing_attachments' => ['nullable', 'array'],
            'existing_attachments.*.name' => ['nullable', 'string', 'max:255'],
            'existing_attachments.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'existing_attachments.*.delete' => ['sometimes', 'boolean'],
            'saveAndStay' => ['sometimes', 'boolean'],
        ];
    }

    public function contentItem(): ?ContentItem
    {
        $routeItem = $this->route('contentItem');

        if ($routeItem instanceof ContentItem) {
            return $routeItem;
        }

        $id = $this->integer('id');

        return $id > 0 ? ContentItem::query()->find($id) : null;
    }

    private function normalizeStatus(mixed $status): ?string
    {
        return match ((string) $status) {
            '3', 'online', 'active', 'published' => 'published',
            '4', 'offline', 'inactive', 'draft', '' => 'draft',
            'archived' => 'archived',
            default => is_string($status) ? $status : null,
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
}
