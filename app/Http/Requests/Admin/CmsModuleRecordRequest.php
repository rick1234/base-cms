<?php

namespace App\Http\Requests\Admin;

use App\Support\Cms\CmsModuleRegistry;
use Illuminate\Foundation\Http\FormRequest;

class CmsModuleRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(CmsModuleRegistry $modules): array
    {
        $definition = $this->definition($modules);
        $editableColumns = $modules->editableColumns($definition);

        $rules = [];

        foreach ($editableColumns as $column) {
            $rules[$column] = $this->rulesForColumn($column, $definition);
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(CmsModuleRegistry $modules): array
    {
        $module = (string) ($this->route('module') ?? $this->route('modulePath'));
        $page = $this->route('page');

        if (is_string($page) && $page !== '') {
            $pageKey = str($page)->beforeLast('.php')->toString();
            $currentDefinition = $modules->findOptional($module);
            $nestedDefinition = $modules->findOptional(trim($module.'/'.$pageKey, '/'));

            if ($nestedDefinition && ! isset(($currentDefinition['pages'] ?? [])[$pageKey])) {
                return $nestedDefinition;
            }

            return $modules->findPage($module, $page);
        }

        return $modules->find($module);
    }

    /**
     * @param  array<string, mixed>  $module
     * @return list<string>
     */
    private function rulesForColumn(string $column, array $module): array
    {
        $rules = ['nullable'];

        if ($column === $module['title_column']) {
            $rules = ['required'];
        }

        if (str_starts_with($column, 'is_') || str_starts_with($column, 'can_') || $column === 'preserve_query') {
            return ['sometimes', 'boolean'];
        }

        if (str_ends_with($column, '_id') || str_ends_with($column, '_order') || in_array($column, ['quantity', 'price', 'sale_price', 'subtotal', 'tax_total', 'shipping_total', 'discount_total', 'grand_total'], true)) {
            return [...$rules, 'integer'];
        }

        if (str_ends_with($column, '_at')) {
            return [...$rules, 'date'];
        }

        if (str_ends_with($column, '_from') || str_ends_with($column, '_until') || in_array($column, ['date', 'starts_at', 'ends_at'], true)) {
            return [...$rules, 'date'];
        }

        if (in_array($column, ['metadata', 'settings', 'payload', 'billing_address', 'shipping_address', 'validation_rules'], true)) {
            return [...$rules, 'json'];
        }

        return [...$rules, 'string', 'max:20000'];
    }
}
