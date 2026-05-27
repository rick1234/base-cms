<?php

namespace App\Actions\Forms;

use App\Mail\FormSubmissionNotification;
use App\Models\Cms\Form;
use App\Models\Cms\FormField;
use App\Models\Cms\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubmitForm
{
    public function handle(Form $form, Request $request): ?FormSubmission
    {
        $form->load(['blocks.rows.fields.options', 'activeRecipients', 'messages']);

        $fields = $form->blocks
            ->flatMap(fn ($block) => $block->rows)
            ->flatMap(fn ($row) => $row->fields)
            ->filter(fn (FormField $field): bool => $field->acceptsSubmissionValue())
            ->values();

        $this->validate($form, $fields, $request);

        [$answers, $payload] = $this->answers($fields, $request);
        $submission = $this->storeSubmission($form, $request, $answers, $payload);

        $this->sendNotifications($form, $submission, $answers);
        $this->sendConfirmation($form, $submission, $answers);

        return $submission;
    }

    /**
     * @param  Collection<int, FormField>  $fields
     */
    private function validate(Form $form, Collection $fields, Request $request): void
    {
        $rules = [];
        $messages = [];

        if (($form->settings['honeypot_enabled'] ?? true) === true) {
            $rules[$form->settings['honeypot_field'] ?? 'website'] = ['nullable', 'prohibited'];
        }

        foreach ($fields as $field) {
            $fieldRules = [];

            if ($field->is_required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            $fieldRules = [
                ...$fieldRules,
                ...$this->typeRules($field),
                ...(array) ($field->validation_rules ?? []),
            ];

            if ($field->supportsOptions()) {
                $allowedValues = $field->options->pluck('value')->filter()->values()->all();

                if ($allowedValues !== []) {
                    if ($field->type === 'checkbox') {
                        $rules[$field->name] = $fieldRules;
                        $rules[$field->name.'.*'] = [Rule::in($allowedValues)];
                    } else {
                        $fieldRules[] = Rule::in($allowedValues);
                    }
                }
            }

            $rules[$field->name] ??= $fieldRules;

            if (filled($field->settings['custom_error_message'] ?? null)) {
                $messages[$field->name.'.required'] = $field->settings['custom_error_message'];
            }
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @return list<string>
     */
    private function typeRules(FormField $field): array
    {
        return match ($field->type) {
            'email' => ['email:rfc'],
            'file' => ['file', 'max:10240'],
            'date' => ['date'],
            'number' => ['numeric'],
            'checkbox' => ['array'],
            default => ['string'],
        };
    }

    /**
     * @param  Collection<int, FormField>  $fields
     * @return array{0: array<string, string>, 1: array<string, mixed>}
     */
    private function answers(Collection $fields, Request $request): array
    {
        $answers = [];
        $payload = [];

        foreach ($fields as $field) {
            $value = $request->file($field->name) ?: $request->input($field->name);

            if ($value instanceof UploadedFile) {
                $path = $value->store('admin/uploads/forms/submissions', 'public');
                $answers[$field->name] = 'storage/'.$path;
                $payload[$field->name] = [
                    'name' => $value->getClientOriginalName(),
                    'path' => $answers[$field->name],
                    'mime' => $value->getClientMimeType(),
                ];

                continue;
            }

            if (is_array($value)) {
                $answers[$field->name] = implode(', ', array_filter($value, fn (mixed $item): bool => filled($item)));
                $payload[$field->name] = array_values($value);

                continue;
            }

            $answers[$field->name] = (string) $value;
            $payload[$field->name] = $value;
        }

        return [$answers, $payload];
    }

    /**
     * @param  array<string, string>  $answers
     * @param  array<string, mixed>  $payload
     */
    private function storeSubmission(Form $form, Request $request, array $answers, array $payload): ?FormSubmission
    {
        if (($form->settings['store_submissions'] ?? true) === false) {
            return null;
        }

        return DB::transaction(function () use ($form, $request, $answers, $payload): FormSubmission {
            $submission = $form->submissions()->create([
                'status' => 'new',
                'locale' => app()->getLocale(),
                'remote_ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'source_url' => $request->headers->get('referer'),
                'payload' => $payload,
            ]);

            $fields = $form->blocks
                ->flatMap(fn ($block) => $block->rows)
                ->flatMap(fn ($row) => $row->fields)
                ->keyBy('name');

            foreach ($answers as $name => $value) {
                $submission->answers()->create([
                    'field_id' => $fields->get($name)?->id,
                    'field_name' => $name,
                    'value' => $value,
                ]);
            }

            return $submission->refresh();
        });
    }

    /**
     * @param  array<string, string>  $answers
     */
    private function sendNotifications(Form $form, ?FormSubmission $submission, array $answers): void
    {
        $message = $this->messageFor($form, 'notification');
        $recipients = $form->activeRecipients->groupBy('type');
        $to = $recipients->get('to', collect())->pluck('email')->filter()->all();
        $cc = $recipients->get('cc', collect())->pluck('email')->filter()->all();
        $bcc = $recipients->get('bcc', collect())->pluck('email')->filter()->all();
        $replyTo = $recipients->get('reply-to', collect())->pluck('email')->filter()->all();

        if ($to === [] && filled($form->recipient_email)) {
            $to = [$form->recipient_email];
        }

        if ($to === []) {
            return;
        }

        $mail = Mail::to($to);

        if ($cc !== []) {
            $mail->cc($cc);
        }

        if ($bcc !== []) {
            $mail->bcc($bcc);
        }

        $mailable = new FormSubmissionNotification(
            form: $form,
            submission: $submission,
            subjectLine: $this->replacePlaceholders($message['subject'], $answers, $form),
            body: $this->replacePlaceholders($message['body'], $answers, $form),
            answers: $this->labelledAnswers($form, $answers),
        );

        if ($replyTo !== []) {
            $mailable->replyTo($replyTo);
        }

        $this->applySender($mailable, $form);

        $mail->send($mailable);
    }

    /**
     * @param  array<string, string>  $answers
     */
    private function sendConfirmation(Form $form, ?FormSubmission $submission, array $answers): void
    {
        $emailField = $form->settings['confirmation_email_field'] ?? null;

        if (! is_string($emailField) || blank($answers[$emailField] ?? null)) {
            return;
        }

        $message = $this->messageFor($form, 'confirmation');

        if (! $message['active']) {
            return;
        }

        $mailable = new FormSubmissionNotification(
            form: $form,
            submission: $submission,
            subjectLine: $this->replacePlaceholders($message['subject'], $answers, $form),
            body: $this->replacePlaceholders($message['body'], $answers, $form),
            answers: $this->labelledAnswers($form, $answers),
        );

        $this->applySender($mailable, $form);

        Mail::to($answers[$emailField])->send($mailable);
    }

    /**
     * @return array{subject: string, body: string, active: bool}
     */
    private function messageFor(Form $form, string $type): array
    {
        $message = $form->messages
            ->where('type', $type)
            ->where('is_active', true)
            ->first();

        if ($message) {
            return [
                'subject' => $message->subject ?: $form->name,
                'body' => $message->body ?: '{{summary}}',
                'active' => true,
            ];
        }

        return [
            'subject' => $type === 'confirmation' ? __('We received your message') : __('New form submission: :form', ['form' => $form->name]),
            'body' => '{{summary}}',
            'active' => $type !== 'confirmation',
        ];
    }

    private function applySender(FormSubmissionNotification $mailable, Form $form): void
    {
        if (filled($form->settings['from_email'] ?? null)) {
            $mailable->from($form->settings['from_email'], $form->settings['from_name'] ?? null);
        }
    }

    /**
     * @param  array<string, string>  $answers
     */
    private function replacePlaceholders(string $content, array $answers, Form $form): string
    {
        $labelledAnswers = $this->labelledAnswers($form, $answers);
        $summary = collect($labelledAnswers)
            ->map(fn (string $value, string $label): string => $label.': '.$value)
            ->implode("\n");

        $replacements = [
            '{{summary}}' => $summary,
            '{summary}' => $summary,
            '{{form_name}}' => $form->name,
            '{form_name}' => $form->name,
        ];

        foreach ($answers as $key => $value) {
            $replacements['{'.$key.'}'] = $value;
            $replacements['{{'.$key.'}}'] = $value;
        }

        return strtr($content, $replacements);
    }

    /**
     * @param  array<string, string>  $answers
     * @return array<string, string>
     */
    private function labelledAnswers(Form $form, array $answers): array
    {
        $labels = $form->blocks
            ->flatMap(fn ($block) => $block->rows)
            ->flatMap(fn ($row) => $row->fields)
            ->mapWithKeys(fn (FormField $field): array => [$field->name => $field->label ?: $field->name]);

        return collect($answers)
            ->mapWithKeys(fn (string $value, string $name): array => [$labels->get($name, $name) => $value])
            ->all();
    }
}
