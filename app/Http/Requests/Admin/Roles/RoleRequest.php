<?php

namespace App\Http\Requests\Admin\Roles;

use App\Models\Cms\CmsRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
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
        $roleId = $this->integer('id') ?: null;

        return [
            'id' => ['nullable', 'integer', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('roles', 'slug')->ignore($roleId)],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name', $this->input('naam'));
        $slug = $this->input('slug');

        $this->merge([
            'name' => $name,
            'slug' => Str::slug((string) ($slug ?: $name)),
            'description' => $this->input('description', $this->input('omschrijving')),
            'status' => $this->normalizedStatus($this->input('status', $this->input('actief', 'active'))),
            'permissions' => $this->input('permissions', []),
        ]);
    }

    public function role(): ?CmsRole
    {
        $id = $this->integer('id');

        return $id > 0 ? CmsRole::query()->findOrFail($id) : null;
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published' => 'active',
            '0', '2', '3', 'inactive', 'draft' => 'inactive',
            default => 'active',
        };
    }
}
