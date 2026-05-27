<?php

namespace App\Http\Requests\Forms;

use Illuminate\Foundation\Http\FormRequest;

class FormSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dynamic field validation is performed by the form submission action
     * because every form stores its own field definitions.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
