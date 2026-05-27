<?php

namespace App\Http\Requests\Admin\Users;

use App\Models\Cms\UserCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UserCategoryRequest extends FormRequest
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
            'id' => ['nullable', 'integer', 'exists:user_categories,id'],
            'parent_id' => ['nullable', 'integer', 'exists:user_categories,id'],
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
            'description' => $this->input('description', $this->input('content')),
            'status' => $this->input('status', 'active'),
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

    public function userCategory(): ?UserCategory
    {
        $id = $this->integer('id');

        return $id > 0 ? UserCategory::query()->findOrFail($id) : null;
    }
}
