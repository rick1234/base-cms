<?php

namespace App\Http\Requests\Admin\Navigation;

use App\Models\Cms\NavigationMenu;
use App\Support\Navigation\NavigationLinkRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NavigationMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $name = (string) $this->input('name');
        $handle = (string) $this->input('handle');

        $this->merge([
            'handle' => Str::slug($handle !== '' ? $handle : $name),
            'domain_id' => $this->filled('domain_id') ? $this->integer('domain_id') : null,
            'locale' => $this->filled('locale') ? Str::lower((string) $this->input('locale')) : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $menu = $this->navigationMenu();

        return [
            'name' => ['required', 'string', 'max:255'],
            'handle' => [
                'required',
                'string',
                'max:80',
                'alpha_dash',
                Rule::unique('navigation_menus', 'handle')
                    ->ignore($menu?->id)
                    ->where(fn ($query) => $query
                        ->where('domain_id', $this->input('domain_id'))
                        ->where('locale', $this->input('locale'))),
            ],
            'domain_id' => ['nullable', 'integer', 'exists:domains,id'],
            'locale' => ['nullable', 'string', 'max:8'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'items_payload' => ['required', 'json'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $items = $this->navigationItems();

            if (! is_array($items)) {
                $validator->errors()->add('items_payload', __('Navigation items are not valid JSON.'));

                return;
            }

            $this->validateItems($items, $validator);
        });
    }

    public function navigationMenu(): ?NavigationMenu
    {
        $routeMenu = $this->route('navigationMenu');

        return $routeMenu instanceof NavigationMenu ? $routeMenu : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function navigationItems(): array
    {
        $decoded = json_decode((string) $this->input('items_payload'), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function validateItems(array $items, mixed $validator, string $prefix = 'items'): void
    {
        $allowedTypes = app(NavigationLinkRegistry::class)->allowedTypes();

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                $validator->errors()->add('items_payload', __('Navigation item :number is invalid.', ['number' => $index + 1]));

                continue;
            }

            $itemValidator = Validator::make($item, [
                'title' => ['required', 'string', 'max:255'],
                'link_type' => ['required', Rule::in($allowedTypes)],
                'link_id' => ['nullable', 'integer', 'min:1'],
                'custom_url' => ['nullable', 'string', 'max:2048'],
                'is_active' => ['sometimes', 'boolean'],
                'expand_children' => ['sometimes', 'boolean'],
                'children' => ['nullable', 'array'],
            ]);

            if ($itemValidator->fails()) {
                foreach ($itemValidator->errors()->all() as $message) {
                    $validator->errors()->add('items_payload', $prefix.'.'.$index.': '.$message);
                }
            }

            $linkType = (string) ($item['link_type'] ?? '');

            if ($linkType === 'custom' && blank($item['custom_url'] ?? null)) {
                $validator->errors()->add('items_payload', $prefix.'.'.$index.': '.__('Custom navigation items need a URL.'));
            }

            if ($linkType !== 'custom' && empty($item['link_id'])) {
                $validator->errors()->add('items_payload', $prefix.'.'.$index.': '.__('Linked navigation items need a selected record.'));
            }

            if (isset($item['children']) && is_array($item['children'])) {
                $this->validateItems($item['children'], $validator, $prefix.'.'.$index.'.children');
            }
        }
    }
}
