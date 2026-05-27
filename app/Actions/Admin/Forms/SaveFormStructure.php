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
            foreach ((array) ($data['blocks'] ?? []) as $blockIndex => $blockData) {
                $this->saveBlock($form, (array) $blockData, (int) $blockIndex, $actor);
            }

            return $form->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveBlock(Form $form, array $data, int $index, ?Authenticatable $actor): void
    {
        $block = $this->ownedBlock($form, (int) ($data['id'] ?? 0));

        if (! empty($data['delete'])) {
            $this->deleteBlock($block);

            return;
        }

        if (! $block && blank($data['title'] ?? null) && empty($data['rows'])) {
            return;
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

        foreach ((array) ($data['rows'] ?? []) as $rowIndex => $rowData) {
            $this->saveRow($block, (array) $rowData, (int) $rowIndex, $actor);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveRow(FormBlock $block, array $data, int $index, ?Authenticatable $actor): void
    {
        $row = $this->ownedRow($block, (int) ($data['id'] ?? 0));

        if (! empty($data['delete'])) {
            $this->deleteRow($row);

            return;
        }

        if (! $row && empty($data['fields'])) {
            return;
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

        foreach ((array) ($data['fields'] ?? []) as $fieldIndex => $fieldData) {
            $this->saveField($row, (array) $fieldData, (int) $fieldIndex, $actor);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveField(FormRow $row, array $data, int $index, ?Authenticatable $actor): void
    {
        $field = $this->ownedField($row, (int) ($data['id'] ?? 0));

        if (! empty($data['delete'])) {
            $this->deleteField($field);

            return;
        }

        if (! $field && blank($data['label'] ?? null) && blank($data['name'] ?? null)) {
            return;
        }

        $field ??= new FormField([
            'row_id' => $row->id,
            'created_by' => $actor?->getAuthIdentifier(),
        ]);

        $type = (string) ($data['type'] ?? 'input');
        $type = $type === 'image_set_choice' ? 'image-set-choice' : $type;
        $name = $this->fieldName($row, (string) ($data['name'] ?? ''), (string) ($data['label'] ?? ''), $field);

        $field->fill([
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

        foreach ((array) ($data['options'] ?? []) as $optionIndex => $optionData) {
            $this->saveOption($field, (array) $optionData, (int) $optionIndex, $actor);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveOption(FormField $field, array $data, int $index, ?Authenticatable $actor): void
    {
        $option = $this->ownedOption($field, (int) ($data['id'] ?? 0));

        if (! empty($data['delete'])) {
            $option?->delete();

            return;
        }

        if (! $option && blank($data['label'] ?? null)) {
            return;
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
        return $id > 0 ? $row->fields()->whereKey($id)->first() : null;
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
