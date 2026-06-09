<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogProductOption;
use App\Models\Cms\CatalogProductOptionValue;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;

class CatalogProductOptionEditor extends Component
{
    public int $productId;

    /**
     * @var list<array<string, mixed>>
     */
    public array $groups = [];

    public ?string $message = null;

    public string $messageLevel = 'success';

    /**
     * @var list<string>
     */
    public array $locales = ['nl', 'en', 'de', 'fr'];

    public function mount(int $productId): void
    {
        $this->ensureAuthorized();

        $this->productId = $productId;
        $this->loadGroups();
    }

    public function addGroup(): void
    {
        $this->groups[] = $this->blankGroup(count($this->groups) + 1);
        $this->message = null;
    }

    public function duplicateGroup(int $index): void
    {
        if (! isset($this->groups[$index])) {
            return;
        }

        $group = $this->groups[$index];
        $group['key'] = $this->rowKey('option-group');
        $group['id'] = null;
        $group['label'] = filled($group['label'] ?? null)
            ? __('Copy of :name', ['name' => $group['label']])
            : __('Option label');
        $group['sort_order'] = count($this->groups) + 1;

        foreach ($group['values'] ?? [] as $valueIndex => $value) {
            $group['values'][$valueIndex]['key'] = $this->rowKey('option-value');
            $group['values'][$valueIndex]['id'] = null;
        }

        $this->groups[] = $group;
        $this->message = null;
    }

    public function removeGroup(int $index): void
    {
        if (! isset($this->groups[$index])) {
            return;
        }

        array_splice($this->groups, $index, 1);
        $this->reindexGroups();
        $this->message = null;
    }

    public function moveGroupUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->groups[$index], $this->groups[$index - 1])) {
            return;
        }

        [$this->groups[$index - 1], $this->groups[$index]] = [$this->groups[$index], $this->groups[$index - 1]];
        $this->reindexGroups();
    }

    public function moveGroupDown(int $index): void
    {
        if (! isset($this->groups[$index], $this->groups[$index + 1])) {
            return;
        }

        [$this->groups[$index + 1], $this->groups[$index]] = [$this->groups[$index], $this->groups[$index + 1]];
        $this->reindexGroups();
    }

    public function addValue(int $groupIndex): void
    {
        if (! isset($this->groups[$groupIndex])) {
            return;
        }

        $values = (array) ($this->groups[$groupIndex]['values'] ?? []);
        $values[] = $this->blankValue(count($values) + 1);
        $this->groups[$groupIndex]['values'] = $values;
        $this->message = null;
    }

    public function removeValue(int $groupIndex, int $valueIndex): void
    {
        if (! isset($this->groups[$groupIndex]['values'][$valueIndex])) {
            return;
        }

        array_splice($this->groups[$groupIndex]['values'], $valueIndex, 1);
        $this->reindexValues($groupIndex);
        $this->message = null;
    }

    public function moveValueUp(int $groupIndex, int $valueIndex): void
    {
        if ($valueIndex <= 0 || ! isset($this->groups[$groupIndex]['values'][$valueIndex], $this->groups[$groupIndex]['values'][$valueIndex - 1])) {
            return;
        }

        [$this->groups[$groupIndex]['values'][$valueIndex - 1], $this->groups[$groupIndex]['values'][$valueIndex]] = [
            $this->groups[$groupIndex]['values'][$valueIndex],
            $this->groups[$groupIndex]['values'][$valueIndex - 1],
        ];
        $this->reindexValues($groupIndex);
    }

    public function moveValueDown(int $groupIndex, int $valueIndex): void
    {
        if (! isset($this->groups[$groupIndex]['values'][$valueIndex], $this->groups[$groupIndex]['values'][$valueIndex + 1])) {
            return;
        }

        [$this->groups[$groupIndex]['values'][$valueIndex + 1], $this->groups[$groupIndex]['values'][$valueIndex]] = [
            $this->groups[$groupIndex]['values'][$valueIndex],
            $this->groups[$groupIndex]['values'][$valueIndex + 1],
        ];
        $this->reindexValues($groupIndex);
    }

    public function save(): void
    {
        $this->ensureAuthorized();

        $data = Validator::make(['groups' => $this->groups], [
            'groups' => ['array'],
            'groups.*.id' => ['nullable', 'integer', 'exists:catalog_product_options,id'],
            'groups.*.label' => ['nullable', 'string', 'max:255'],
            'groups.*.label_translations' => ['array'],
            'groups.*.label_translations.*' => ['nullable', 'string', 'max:255'],
            'groups.*.values' => ['array'],
            'groups.*.values.*.id' => ['nullable', 'integer', 'exists:catalog_product_option_values,id'],
            'groups.*.values.*.value' => ['nullable', 'string', 'max:255'],
            'groups.*.values.*.value_translations' => ['array'],
            'groups.*.values.*.value_translations.*' => ['nullable', 'string', 'max:255'],
        ])->validate();

        DB::transaction(function () use ($data): void {
            $product = $this->product();
            $seenGroupIds = [];

            foreach (array_values($data['groups'] ?? []) as $groupIndex => $row) {
                if ($this->groupIsBlank($row)) {
                    continue;
                }

                $group = $this->existingGroup((int) ($row['id'] ?? 0)) ?? new CatalogProductOption([
                    'catalog_product_id' => $product->id,
                    'created_by' => auth()->id(),
                ]);
                $labelTranslations = $this->cleanTranslations((array) ($row['label_translations'] ?? []));
                $label = $this->fallbackText((string) ($row['label'] ?? ''), $labelTranslations, __('Option label'));

                $group->fill([
                    'label' => $label,
                    'label_translations' => $labelTranslations,
                    'sort_order' => $groupIndex + 1,
                    'updated_by' => auth()->id(),
                ])->save();

                $seenGroupIds[] = $group->id;
                $seenValueIds = [];

                foreach (array_values((array) ($row['values'] ?? [])) as $valueIndex => $valueRow) {
                    if ($this->valueIsBlank($valueRow)) {
                        continue;
                    }

                    $value = $this->existingValue($group, (int) ($valueRow['id'] ?? 0)) ?? new CatalogProductOptionValue([
                        'catalog_product_option_id' => $group->id,
                        'created_by' => auth()->id(),
                    ]);
                    $valueTranslations = $this->cleanTranslations((array) ($valueRow['value_translations'] ?? []));
                    $fallbackValue = $this->fallbackText((string) ($valueRow['value'] ?? ''), $valueTranslations, __('Option'));

                    $value->fill([
                        'value' => $fallbackValue,
                        'value_translations' => $valueTranslations,
                        'sort_order' => $valueIndex + 1,
                        'updated_by' => auth()->id(),
                    ])->save();

                    $seenValueIds[] = $value->id;
                }

                $group->values()
                    ->when($seenValueIds !== [], fn ($query) => $query->whereNotIn('id', $seenValueIds))
                    ->delete();
            }

            $product->options()
                ->when($seenGroupIds !== [], fn ($query) => $query->whereNotIn('id', $seenGroupIds))
                ->delete();
        });

        $this->loadGroups();
        $this->messageLevel = 'success';
        $this->message = __('Product options saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.catalog.product-option-editor');
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);
    }

    private function product(): CatalogProduct
    {
        return CatalogProduct::query()
            ->with(['options.values'])
            ->findOrFail($this->productId);
    }

    private function loadGroups(): void
    {
        $this->groups = $this->product()
            ->options
            ->values()
            ->map(fn (CatalogProductOption $option, int $index): array => [
                'key' => $this->rowKey('option-group'),
                'id' => $option->id,
                'label' => $option->label,
                'label_translations' => $this->translationsForForm($option->label_translations, $option->label),
                'sort_order' => $option->sort_order ?: $index + 1,
                'values' => $option->values
                    ->values()
                    ->map(fn (CatalogProductOptionValue $value, int $valueIndex): array => [
                        'key' => $this->rowKey('option-value'),
                        'id' => $value->id,
                        'value' => $value->value,
                        'value_translations' => $this->translationsForForm($value->value_translations, $value->value),
                        'sort_order' => $value->sort_order ?: $valueIndex + 1,
                    ])
                    ->all(),
            ])
            ->all();

        if ($this->groups === []) {
            $this->addGroup();
        }
    }

    private function existingGroup(int $id): ?CatalogProductOption
    {
        return $id > 0 ? $this->product()->options()->whereKey($id)->first() : null;
    }

    private function existingValue(CatalogProductOption $group, int $id): ?CatalogProductOptionValue
    {
        return $id > 0 ? $group->values()->whereKey($id)->first() : null;
    }

    /**
     * @param  array<string, mixed>|null  $translations
     * @return array<string, string>
     */
    private function translationsForForm(?array $translations, ?string $fallback): array
    {
        return collect($this->locales)
            ->mapWithKeys(fn (string $locale): array => [$locale => (string) ($translations[$locale] ?? ($locale === config('cms.default_locale', 'nl') ? $fallback : ''))])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, string>
     */
    private function cleanTranslations(array $translations): array
    {
        return collect($this->locales)
            ->mapWithKeys(fn (string $locale): array => [$locale => trim((string) ($translations[$locale] ?? ''))])
            ->filter(fn (string $value): bool => $value !== '')
            ->all();
    }

    /**
     * @param  array<string, string>  $translations
     */
    private function fallbackText(string $fallback, array $translations, string $default): string
    {
        $fallback = trim($fallback);

        if ($fallback !== '') {
            return $fallback;
        }

        return collect($this->locales)
            ->map(fn (string $locale): ?string => $translations[$locale] ?? null)
            ->filter(fn (?string $value): bool => filled($value))
            ->first() ?: $default;
    }

    /**
     * @param  array<string, mixed>  $group
     */
    private function groupIsBlank(array $group): bool
    {
        return blank($group['label'] ?? null)
            && $this->cleanTranslations((array) ($group['label_translations'] ?? [])) === []
            && collect((array) ($group['values'] ?? []))->every(fn (array $value): bool => $this->valueIsBlank($value));
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function valueIsBlank(array $value): bool
    {
        return blank($value['value'] ?? null)
            && $this->cleanTranslations((array) ($value['value_translations'] ?? [])) === [];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankGroup(int $sortOrder): array
    {
        return [
            'key' => $this->rowKey('option-group'),
            'id' => null,
            'label' => '',
            'label_translations' => $this->translationsForForm([], null),
            'sort_order' => $sortOrder,
            'values' => [$this->blankValue(1)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankValue(int $sortOrder): array
    {
        return [
            'key' => $this->rowKey('option-value'),
            'id' => null,
            'value' => '',
            'value_translations' => $this->translationsForForm([], null),
            'sort_order' => $sortOrder,
        ];
    }

    private function reindexGroups(): void
    {
        foreach ($this->groups as $index => $group) {
            $this->groups[$index]['sort_order'] = $index + 1;
        }
    }

    private function reindexValues(int $groupIndex): void
    {
        foreach (($this->groups[$groupIndex]['values'] ?? []) as $index => $value) {
            $this->groups[$groupIndex]['values'][$index]['sort_order'] = $index + 1;
        }
    }

    private function rowKey(string $prefix): string
    {
        return $prefix.'-'.Str::uuid()->toString();
    }
}
