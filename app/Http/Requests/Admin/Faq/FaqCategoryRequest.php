<?php

namespace App\Http\Requests\Admin\Faq;

use App\Models\Cms\FaqCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FaqCategoryRequest extends FormRequest
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
            'id' => ['nullable', 'integer', 'exists:faq_categories,id'],
            'parent_id' => ['nullable', 'integer', 'exists:faq_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'parent_id' => $this->input('parent_id', $this->input('parent')),
            'name' => $this->input('name', $this->input('naam')),
            'description' => $this->input('description', $this->input('omschrijving', $this->input('content'))),
            'status' => $this->normalizedStatus($this->input('status', 'active')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->integer('id') > 0 && $this->integer('id') === $this->integer('parent_id')) {
                $validator->errors()->add('parent_id', __('A category cannot be its own parent.'));
            }
        });
    }

    public function faqCategory(): ?FaqCategory
    {
        $id = $this->integer('id');

        return $id > 0 ? FaqCategory::query()->findOrFail($id) : null;
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active' => 'active',
            '0', '2', '3', 'inactive' => 'inactive',
            default => 'active',
        };
    }
}
