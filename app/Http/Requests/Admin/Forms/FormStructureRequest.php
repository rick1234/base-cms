<?php

namespace App\Http\Requests\Admin\Forms;

use App\Models\Cms\Form;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormStructureRequest extends FormRequest
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
            'id' => ['required', 'integer', 'exists:forms,id'],
            'blocks' => ['array'],
            'blocks.*.id' => ['nullable', 'integer', 'exists:form_blocks,id'],
            'blocks.*.title' => ['nullable', 'string', 'max:255'],
            'blocks.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'blocks.*.css_class' => ['nullable', 'string', 'max:255'],
            'blocks.*.delete' => ['boolean'],
            'blocks.*.rows' => ['array'],
            'blocks.*.rows.*.id' => ['nullable', 'integer', 'exists:form_rows,id'],
            'blocks.*.rows.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'blocks.*.rows.*.width' => ['nullable', 'integer', 'min:10', 'max:100'],
            'blocks.*.rows.*.css_class' => ['nullable', 'string', 'max:255'],
            'blocks.*.rows.*.delete' => ['boolean'],
            'blocks.*.rows.*.fields' => ['array'],
            'blocks.*.rows.*.fields.*.id' => ['nullable', 'integer', 'exists:form_fields,id'],
            'blocks.*.rows.*.fields.*.name' => ['nullable', 'string', 'max:64'],
            'blocks.*.rows.*.fields.*.label' => ['nullable', 'string', 'max:255'],
            'blocks.*.rows.*.fields.*.type' => ['nullable', 'string', Rule::in([...array_keys(Form::fieldTypes()), 'image_set_choice'])],
            'blocks.*.rows.*.fields.*.help_text' => ['nullable', 'string'],
            'blocks.*.rows.*.fields.*.is_required' => ['boolean'],
            'blocks.*.rows.*.fields.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'blocks.*.rows.*.fields.*.validation_rules' => ['nullable', 'string'],
            'blocks.*.rows.*.fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'blocks.*.rows.*.fields.*.default_value' => ['nullable', 'string', 'max:255'],
            'blocks.*.rows.*.fields.*.label_visible' => ['boolean'],
            'blocks.*.rows.*.fields.*.width' => ['nullable', 'integer', 'min:10', 'max:100'],
            'blocks.*.rows.*.fields.*.custom_error_message' => ['nullable', 'string', 'max:255'],
            'blocks.*.rows.*.fields.*.information' => ['nullable', 'string'],
            'blocks.*.rows.*.fields.*.css_class' => ['nullable', 'string', 'max:255'],
            'blocks.*.rows.*.fields.*.delete' => ['boolean'],
            'blocks.*.rows.*.fields.*.options' => ['array'],
            'blocks.*.rows.*.fields.*.options.*.id' => ['nullable', 'integer', 'exists:form_field_options,id'],
            'blocks.*.rows.*.fields.*.options.*.label' => ['nullable', 'string', 'max:255'],
            'blocks.*.rows.*.fields.*.options.*.value' => ['nullable', 'string', 'max:255'],
            'blocks.*.rows.*.fields.*.options.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'blocks.*.rows.*.fields.*.options.*.image_path' => ['nullable', 'string', 'max:255'],
            'blocks.*.rows.*.fields.*.options.*.description' => ['nullable', 'string', 'max:255'],
            'blocks.*.rows.*.fields.*.options.*.delete' => ['boolean'],
        ];
    }

    public function formModel(): Form
    {
        return Form::query()->findOrFail($this->integer('id'));
    }
}
