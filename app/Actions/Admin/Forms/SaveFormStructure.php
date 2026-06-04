<?php

namespace App\Actions\Admin\Forms;

use App\Models\Cms\Form;
use App\Models\Cms\FormBlock;
use App\Models\Cms\FormField;
use App\Models\Cms\FormFieldOption;
use App\Models\Cms\FormRow;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaveFormStructure
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Form $form, array $data, ?Authenticatable $actor = null): Form
    {
        return DB::transaction(function () use ($form, $data, $actor): Form {
            $savedBlockIds = [];

            foreach ((array) ($data['blocks'] ?? []) as $blockIndex => $blockData) {
                $savedBlockId = $this->saveBlock($form, (array) $blockData, (int) $blockIndex, $actor);

                if ($savedBlockId !== null) {
                    $savedBlockIds[] = $savedBlockId;
                }
            }

            $form->blocks()
                ->when($savedBlockIds !== [], fn ($query) => $query->whereKeyNot($savedBlockIds))
                ->get()
                ->each(fn (FormBlock $block) => $this->deleteBlock($block));

            return $form->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveBlock(Form $form, array $data, int $index, ?Authenticatable $actor): ?int
    {
        $block = $this->ownedBlock($form, (int) ($data['id'] ?? 0));

        if (! empty($data['delete'])) {
            $this->deleteBlock($block);

            return null;
        }

        if (! $block && blank($data['title'] ?? null) && empty($data['rows'])) {
            return null;
        }

        $block ??= new FormBlock([
            'form_id' => $form->id,
            'created_by' => $actor?->getAuthIdentifier(),
        ]);
        $block->fill([
            'title' => $data['title'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? ($index + 1)),
            'settings' => [
                'css_class' => $data['css_class'] ?? null,
            ],
            'updated_by' => $actor?->getAuthIdentifier(),
        ])->save();

        $savedRowIds = [];

        foreach ((array) ($data['rows'] ?? []) as $rowIndex => $rowData) {
            $savedRowId = $this->saveRow($block, (array) $rowData, (int) $rowIndex, $actor);

            if ($savedRowId !== null) {
                $savedRowIds[] = $savedRowId;
            }
        }

        $block->rows()
            ->when($savedRowIds !== [], fn ($query) => $query->whereKeyNot($savedRowIds))
            ->get()
            ->each(fn (FormRow $row) => $this->deleteRow($row));

        return (int) $block->id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveRow(FormBlock $block, array $data, int $index, ?Authenticatable $actor): ?int
    {
        $row = $this->ownedRow($block, (int) ($data['id'] ?? 0));

        if (! empty($data['delete'])) {
            $this->deleteRow($row);

            return null;
        }

        if (! $row && empty($data['fields'])) {
            return null;
        }

        $row ??= new FormRow([
            'block_id' => $block->id,
            'created_by' => $actor?->getAuthIdentifier(),
        ]);
        $row->fill([
            'sort_order' => (int) ($data['sort_order'] ?? ($index + 1)),
            'settings' => [
                'width' => (int) ($data['width'] ?? 100),
                'css_class' => $data['css_class'] ?? null,
            ],
            'updated_by' => $actor?->getAuthIdentifier(),
        ])->save();

        $savedFieldIds = [];

        foreach ((array) ($data['fields'] ?? []) as $fieldIndex => $fieldData) {
            $savedFieldId = $this->saveField($row, (array) $fieldData, (int) $fieldIndex, $actor);

            if ($savedFieldId !== null) {
                $savedFieldIds[] = $savedFieldId;
            }
        }

        $row->fields()
            ->when($savedFieldIds !== [], fn ($query) => $query->whereKeyNot($savedFieldIds))
            ->get()
            ->each(fn (FormField $field) => $this->deleteField($field));

        return (int) $row->id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveField(FormRow $row, array $data, int $index, ?Authenticatable $actor): ?int
    {
        $field = $this->ownedField($row, (int) ($data['id'] ?? 0));

        if (! empty($data['delete'])) {
            $this->deleteField($field);

            return null;
        }

        if (! $field && blank($data['label'] ?? null) && blank($data['name'] ?? null)) {
            return null;
        }

        $field ??= new FormField([
            'row_id' => $row->id,
            'created_by' => $actor?->getAuthIdentifier(),
        ]);

        $type = (string) ($data['type'] ?? 'input');
        $type = $type === 'image_set_choice' ? 'image-set-choice' : $type;
        $name = $this->fieldName($row, (string) ($data['name'] ?? ''), (string) ($data['label'] ?? ''), $field);

        $field->fill([
            'row_id' => $row->id,
            'name' => $name,
            'label' => $data['label'] ?? null,
            'type' => $type,
            'help_text' => $data['help_text'] ?? null,
            'is_required' => (bool) ($data['is_required'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? ($index + 1)),
            'validation_rules' => $this->validationRules((string) ($data['validation_rules'] ?? '')),
            'settings' => [
                'placeholder' => $data['placeholder'] ?? null,
                'default_value' => $data['default_value'] ?? null,
                'label_visible' => (bool) ($data['label_visible'] ?? true),
                'width' => (int) ($data['width'] ?? 100),
                'custom_error_message' => $data['custom_error_message'] ?? null,
                'information' => $data['information'] ?? null,
                'css_class' => $data['css_class'] ?? null,
            ],
            'updated_by' => $actor?->getAuthIdentifier(),
        ])->save();

        $savedOptionIds = [];

        foreach ((array) ($data['options'] ?? []) as $optionIndex => $optionData) {
            $savedOptionId = $this->saveOption($field, (array) $optionData, (int) $optionIndex, $actor);

            if ($savedOptionId !== null) {
                $savedOptionIds[] = $savedOptionId;
            }
        }

        $field->options()
            ->when($savedOptionIds !== [], fn ($query) => $query->whereKeyNot($savedOptionIds))
            ->get()
            ->each(fn (FormFieldOption $option) => $option->delete());

        return (int) $field->id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveOption(FormField $field, array $data, int $index, ?Authenticatable $actor): ?int
    {
        $option = $this->ownedOption($field, (int) ($data['id'] ?? 0));

        if (! empty($data['delete'])) {
            $option?->delete();

            return null;
        }

        if (! $option && blank($data['label'] ?? null)) {
            return null;
        }

        $option ??= new FormFieldOption([
            'field_id' => $field->id,
            'created_by' => $actor?->getAuthIdentifier(),
        ]);
        $option->fill([
            'label' => $data['label'] ?? '',
            'value' => filled($data['value'] ?? null) ? $data['value'] : Str::slug((string) ($data['label'] ?? '')),
            'sort_order' => (int) ($data['sort_order'] ?? ($index + 1)),
            'settings' => [
                'image_path' => $data['image_path'] ?? null,
                'description' => $data['description'] ?? null,
            ],
            'updated_by' => $actor?->getAuthIdentifier(),
        ])->save();

        return (int) $option->id;
    }

    private function fieldName(FormRow $row, string $candidate, string $label, ?FormField $field): string
    {
        $base = filled($candidate) ? $candidate : $label;
        $base = Str::of($base)->slug('_')->lower()->toString() ?: 'field';

        $query = FormField::query()
            ->where('name', $base)
            ->whereHas('row.block', fn ($query) => $query->where('form_id', $row->block->form_id));

        if ($field?->exists) {
            $query->whereKeyNot($field->id);
        }

        if (! $query->exists()) {
            return $base;
        }

        return $base.'_'.Str::lower(Str::random(4));
    }

    /**
     * @return list<string>
     */
    private function validationRules(string $rules): array
    {
        return collect(preg_split('/[\r\n|]+/', $rules) ?: [])
            ->map(fn (string $rule): string => trim($rule))
            ->filter()
            ->values()
            ->all();
    }

    private function ownedBlock(Form $form, int $id): ?FormBlock
    {
        return $id > 0 ? $form->blocks()->whereKey($id)->first() : null;
    }

    private function ownedRow(FormBlock $block, int $id): ?FormRow
    {
        return $id > 0 ? $block->rows()->whereKey($id)->first() : null;
    }

    private function ownedField(FormRow $row, int $id): ?FormField
    {
        if ($id <= 0) {
            return null;
        }

        $row->loadMissing('block');

        return FormField::query()
            ->whereKey($id)
            ->whereHas('row.block', fn ($query) => $query->where('form_id', $row->block->form_id))
            ->first();
    }

    private function ownedOption(FormField $field, int $id): ?FormFieldOption
    {
        return $id > 0 ? $field->options()->whereKey($id)->first() : null;
    }

    private function deleteBlock(?FormBlock $block): void
    {
        if (! $block) {
            return;
        }

        $block->rows->each(fn (FormRow $row) => $this->deleteRow($row));
        $block->delete();
    }

    private function deleteRow(?FormRow $row): void
    {
        if (! $row) {
            return;
        }

        $row->fields->each(fn (FormField $field) => $this->deleteField($field));
        $row->delete();
    }

    private function deleteField(?FormField $field): void
    {
        if (! $field) {
            return;
        }

        $field->options()->delete();
        $field->delete();
    }
}
