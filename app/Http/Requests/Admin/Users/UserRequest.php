<?php

namespace App\Http\Requests\Admin\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $userId = $this->integer('id') ?: null;

        return [
            'id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:8'],
            'locale' => ['nullable', 'string', 'max:8'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
            'is_active' => ['boolean'],
            'is_admin' => ['boolean'],
            'salutation' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:16'],
            'street' => ['nullable', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:255'],
            'house_number_addition' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'max:8'],
            'phone' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:user_categories,id'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $firstName = $this->input('first_name', $this->input('voornaam'));
        $middleName = $this->input('middle_name', $this->input('tussenvoegsel'));
        $lastName = $this->input('last_name', $this->input('achternaam'));
        $username = $this->input('username', $this->input('userUsername'));
        $email = $this->input('email');
        $name = $this->input('name') ?: trim(collect([$firstName, $middleName, $lastName])->filter()->join(' ')) ?: $username ?: $email;
        $roleIds = $this->normalizedRoleIds();

        $this->merge([
            'name' => $name,
            'username' => $username,
            'password' => $this->input('password', $this->input('userPassword')),
            'locale' => $this->input('locale', $this->input('taalcode')),
            'active_from' => $this->normalizedDate('active_from', 'startdatum'),
            'active_until' => $this->normalizedDate('active_until', 'einddatum'),
            'is_active' => $this->normalizedBoolean('is_active', 'actief', true),
            'is_admin' => $this->normalizedBoolean('is_admin', null, false),
            'salutation' => $this->input('salutation', $this->input('aanhef')),
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'gender' => $this->input('gender', $this->input('geslacht')),
            'street' => $this->input('street', $this->input('straat')),
            'house_number' => $this->input('house_number', $this->input('huisnummer')),
            'house_number_addition' => $this->input('house_number_addition', $this->input('huisnummertoevoeging')),
            'postal_code' => $this->input('postal_code', $this->input('postcode')),
            'city' => $this->input('city', $this->input('plaats')),
            'country_code' => $this->input('country_code', $this->input('landcode')),
            'phone' => $this->input('phone', $this->input('telefoon')),
            'company_name' => $this->input('company_name', $this->input('bedrijfsnaam')),
            'role_id' => $this->input('role_id') ?: ($roleIds[0] ?? null),
            'roles' => $roleIds,
            'categories' => $this->input('categories', $this->input('categorie', [])),
        ]);
    }

    public function userRecord(): ?User
    {
        $id = $this->integer('id');

        return $id > 0 ? User::query()->findOrFail($id) : null;
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

    private function normalizedBoolean(string $key, ?string $legacyKey, bool $default): bool
    {
        if ($this->has($key)) {
            return $this->boolean($key);
        }

        if ($legacyKey && $this->has($legacyKey)) {
            return $this->boolean($legacyKey);
        }

        return $default;
    }

    /**
     * @return list<int>
     */
    private function normalizedRoleIds(): array
    {
        $roles = $this->input('roles', []);

        if (blank($roles)) {
            $roles = [];
        }

        if (! is_array($roles)) {
            $roles = [$roles];
        }

        if ($this->filled('role_id')) {
            $roles[] = $this->input('role_id');
        }

        return collect($roles)
            ->map(fn (mixed $roleId): int => (int) $roleId)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
