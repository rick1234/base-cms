<?php

namespace App\Http\Requests\Admin\Faq;

use App\Models\Cms\FaqItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FaqItemRequest extends FormRequest
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
        $faqItemId = $this->integer('id') ?: null;

        return [
            'id' => ['nullable', 'integer', 'exists:faq_items,id'],
            'question' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('faq_items', 'slug')->ignore($faqItemId)],
            'locale' => ['nullable', 'string', 'max:8'],
            'intro' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:published,draft,archived'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:faq_categories,id'],
            'attachment_files' => ['array'],
            'attachment_files.*' => ['file', 'max:10240'],
            'attachment_names' => ['array'],
            'attachment_names.*' => ['nullable', 'string', 'max:255'],
            'existing_attachments' => ['array'],
            'existing_attachments.*.name' => ['nullable', 'string', 'max:255'],
            'existing_attachments.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'existing_attachments.*.delete' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'question' => $this->input('question', $this->input('vraag')),
            'body' => $this->input('body', $this->input('answer', $this->input('antwoord'))),
            'status' => $this->normalizedStatus($this->input('status', 'published')),
            'active_from' => $this->normalizedDate('active_from', 'startdatum'),
            'active_until' => $this->normalizedDate('active_until', 'einddatum'),
            'categories' => $this->input('categories', $this->input('categorie', [])),
            'attachment_files' => $this->file('attachment_files') ?: $this->file('attachment'),
            'attachment_names' => $this->input('attachment_names', $this->input('attachmentNaam', [])),
        ]);
    }

    public function faqItem(): ?FaqItem
    {
        $id = $this->integer('id');

        return $id > 0 ? FaqItem::query()->findOrFail($id) : null;
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published' => 'published',
            '0', '2', '3', 'inactive', 'draft' => 'draft',
            'archived' => 'archived',
            default => 'published',
        };
    }

    private function normalizedDate(string $key, string $legacyKey): ?string
    {
        $value = $this->input($key, $this->input($legacyKey));

        if (blank($value)) {
            return null;
        }

        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', (string) $value) === 1) {
            return Str::of((string) $value)->explode('-')->reverse()->join('-');
        }

        return (string) $value;
    }
}
