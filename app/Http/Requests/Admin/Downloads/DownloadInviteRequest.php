<?php

namespace App\Http\Requests\Admin\Downloads;

use App\Models\Cms\Download;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DownloadInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $emails = preg_split('/[\s,;]+/', (string) $this->input('emails'), -1, PREG_SPLIT_NO_EMPTY);

        $this->merge([
            'emails' => collect($emails ?: [])
                ->map(fn (string $email): string => mb_strtolower(trim($email)))
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'emails' => ['required', 'array', 'min:1', 'max:100'],
            'emails.*' => ['required', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->download()?->hasFile()) {
                    $validator->errors()->add('emails', __('Save a file before sending download invites.'));
                }
            },
        ];
    }

    /**
     * @return list<string>
     */
    public function emails(): array
    {
        return $this->validated('emails');
    }

    public function messageText(): ?string
    {
        $message = $this->validated('message');

        return is_string($message) && trim($message) !== '' ? trim($message) : null;
    }

    private function download(): ?Download
    {
        $download = $this->route('download');

        return $download instanceof Download ? $download : null;
    }
}
