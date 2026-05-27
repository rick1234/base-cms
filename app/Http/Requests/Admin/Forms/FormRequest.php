<?php

namespace App\Http\Requests\Admin\Forms;

use App\Models\Cms\Form;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FormRequest extends BaseFormRequest
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
        $formId = $this->integer('id') ?: null;

        return [
            'id' => ['nullable', 'integer', 'exists:forms,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('forms', 'slug')->ignore($formId)],
            'locale' => ['nullable', 'string', 'max:8'],
            'description' => ['nullable', 'string'],
            'submit_text' => ['nullable', 'string', 'max:255'],
            'success_message' => ['nullable', 'string'],
            'recipient_email' => ['nullable', 'email:rfc', 'max:255'],
            'status' => ['required', 'string', 'in:published,draft,archived'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:form_categories,id'],
            'show_title' => ['boolean'],
            'layout' => ['nullable', 'string', 'max:64'],
            'mail_template' => ['nullable', 'string', 'max:255'],
            'store_submissions' => ['boolean'],
            'honeypot_enabled' => ['boolean'],
            'honeypot_field' => ['nullable', 'string', 'max:64'],
            'confirmation_email_field' => ['nullable', 'string', 'max:64'],
            'redirect_url' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email:rfc', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'recipients' => ['array'],
            'recipients.*.id' => ['nullable', 'integer', 'exists:form_recipients,id'],
            'recipients.*.name' => ['nullable', 'string', 'max:255'],
            'recipients.*.email' => ['nullable', 'email:rfc', 'max:255'],
            'recipients.*.type' => ['nullable', 'string', 'in:to,cc,bcc,reply-to'],
            'recipients.*.is_active' => ['boolean'],
            'recipients.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'recipients.*.delete' => ['boolean'],
            'messages' => ['array'],
            'messages.*.id' => ['nullable', 'integer', 'exists:form_messages,id'],
            'messages.*.name' => ['nullable', 'string', 'max:255'],
            'messages.*.subject' => ['nullable', 'string', 'max:255'],
            'messages.*.body' => ['nullable', 'string'],
            'messages.*.type' => ['nullable', 'string', 'in:notification,confirmation,internal'],
            'messages.*.is_active' => ['boolean'],
            'messages.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'messages.*.layout' => ['nullable', 'string', 'max:64'],
            'messages.*.preheader' => ['nullable', 'string', 'max:255'],
            'messages.*.delete' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->input('name', $this->input('title')),
            'description' => $this->input('description', $this->input('omschrijving')),
            'submit_text' => $this->input('submit_text', $this->input('submit_button_text', $this->input('button_text'))),
            'recipient_email' => $this->input('recipient_email', $this->input('receiver')),
            'status' => $this->normalizedStatus($this->input('status', 'published')),
            'categories' => $this->input('categories', $this->input('categorie', [])),
            'show_title' => $this->normalizedBoolean('show_title', false),
            'store_submissions' => $this->normalizedBoolean('store_submissions', true),
            'honeypot_enabled' => $this->normalizedBoolean('honeypot_enabled', true),
        ]);
    }

    public function formModel(): ?Form
    {
        $id = $this->integer('id');

        return $id > 0 ? Form::query()->findOrFail($id) : null;
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'online', 'published' => 'published',
            '0', '2', '3', 'inactive', 'offline', 'draft' => 'draft',
            'archived' => 'archived',
            default => 'published',
        };
    }

    private function normalizedBoolean(string $key, bool $default): bool
    {
        $value = $this->input($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return Str::of($value)->lower()->is(['1', 'true', 'yes', 'ja', 'on']);
        }

        return (bool) $value;
    }
}
