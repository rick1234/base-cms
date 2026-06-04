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
        $activeTab = $this->string('active_tab')->toString() ?: 'info';
        $data = ['active_tab' => $activeTab];

        if ($this->hasAny(['title', 'titel'])) {
            $data['title'] = $this->input('title', $this->input('titel'));
        }

        if ($this->hasAny(['subtitle', 'subtitel'])) {
            $data['subtitle'] = $this->input('subtitle', $this->input('subtitel'));
        }

        if ($this->has('slug')) {
            $data['slug'] = $this->input('slug');
        }

        if ($this->hasAny(['locale', 'taalcode'])) {
            $data['locale'] = $this->input('locale', $this->input('taalcode'));
        }

        if ($this->has('meta_description')) {
            $data['meta_description'] = $this->input('meta_description');
        }

        if ($this->has('status')) {
            $data['status'] = $this->normalizeStatus($this->input('status'));
        }

        if ($this->hasAny(['active_from', 'startdatum'])) {
            $data['active_from'] = $this->normalizeDate($this->input('active_from', $this->input('startdatum')));
        }

        if ($this->hasAny(['active_until', 'einddatum'])) {
            $data['active_until'] = $this->normalizeDate($this->input('active_until', $this->input('einddatum')));
        }

        if ($this->hasAny(['form_id', 'formulier_id'])) {
            $data['form_id'] = $this->input('form_id', $this->input('formulier_id'));
        }

        if ($this->hasAny(['slider_category_id', 'slider'])) {
            $data['slider_category_id'] = $this->input('slider_category_id', $this->input('slider'));
        }

        if ($this->hasAny(['categories', 'categorie'])) {
            $data['categories'] = $this->input('categories', $this->input('categorie'));
        }

        if ($contentItem = $this->contentItem()) {
            $data = $this->preserveExistingTabValues($data, $contentItem, $activeTab);
        }

        $this->merge($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
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
            'active_tab' => ['sometimes', Rule::in(['info', 'seo', 'form'])],
            'saveAndStay' => ['sometimes', 'boolean'],
        ];
    }

    public function contentItem(): ?ContentItem
    {
        $routeItem = $this->route('contentItem');

        if ($routeItem instanceof ContentItem) {
            return $routeItem;
        }

        $id = (int) ($this->route('id') ?: $this->integer('id'));

        return $id > 0 ? ContentItem::query()->find($id) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preserveExistingTabValues(array $data, ContentItem $contentItem, string $activeTab): array
    {
        $preserved = [
            'title' => $contentItem->title,
            'subtitle' => $contentItem->subtitle,
            'slug' => $contentItem->slug,
            'locale' => $contentItem->locale,
            'meta_description' => $contentItem->meta_description,
            'status' => $contentItem->status,
            'active_from' => optional($contentItem->active_from)->format('Y-m-d'),
            'active_until' => optional($contentItem->active_until)->format('Y-m-d'),
            'form_id' => $contentItem->form_id,
            'slider_category_id' => $contentItem->slider_category_id,
            'categories' => $contentItem->categories()->pluck('content_categories.id')->all(),
        ];

        foreach ($preserved as $field => $value) {
            if (array_key_exists($field, $data) || ! $this->shouldPreserveField($field, $activeTab)) {
                continue;
            }

            $data[$field] = $value;
        }

        return $data;
    }

    private function shouldPreserveField(string $field, string $activeTab): bool
    {
        return match ($activeTab) {
            'seo' => $field !== 'meta_description',
            'form' => $field !== 'form_id',
            default => in_array($field, ['meta_description', 'form_id', 'slider_category_id'], true),
        };
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
