<?php

namespace App\Http\Requests\Admin\Navigation;

use App\Models\Cms\Domain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NavigationMenuIndexRequest extends FormRequest
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
            'id' => ['nullable', 'integer', 'min:1'],
            'name' => ['nullable', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:80'],
            'locale' => ['nullable', 'string', 'max:16'],
            'domain_id' => [
                'nullable',
                Rule::when(
                    $this->input('domain_id') !== 'global',
                    ['integer', Rule::exists(Domain::class, 'id')],
                    ['string', Rule::in(['global'])],
                ),
            ],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'items_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
