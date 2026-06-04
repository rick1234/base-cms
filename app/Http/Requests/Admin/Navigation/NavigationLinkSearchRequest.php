<?php

namespace App\Http\Requests\Admin\Navigation;

use App\Support\Navigation\NavigationLinkRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NavigationLinkSearchRequest extends FormRequest
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
            'type' => ['required', Rule::in(app(NavigationLinkRegistry::class)->allowedTypes())],
            'q' => ['nullable', 'string', 'max:100'],
            'locale' => ['nullable', 'string', 'max:8'],
            'all_languages' => ['sometimes', 'boolean'],
        ];
    }
}
