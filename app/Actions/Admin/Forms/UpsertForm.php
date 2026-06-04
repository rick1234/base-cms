<?php

namespace App\Actions\Admin\Forms;

use App\Models\Cms\Form;
use App\Models\Cms\FormMessage;
use App\Models\Cms\FormRecipient;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpsertForm
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?Authenticatable $actor = null, ?Form $form = null): Form
    {
        return DB::transaction(function () use ($data, $actor, $form): Form {
            $form ??= new Form;
            $wasExisting = $form->exists;

            $attributes = Arr::only($data, [
                'name',
                'slug',
                'locale',
                'description',
                'submit_text',
                'success_message',
                'recipient_email',
                'status',
                'sort_order',
            ]);

            $attributes['slug'] = filled($attributes['slug'] ?? null)
                ? Str::slug((string) $attributes['slug'])
                : Str::slug((string) $attributes['name']);
            $attributes['settings'] = $this->settings($data, (array) ($form->settings ?? []));

            if (! $form->exists) {
                $attributes['created_by'] = $actor?->getAuthIdentifier();
            }

            $attributes['updated_by'] = $actor?->getAuthIdentifier();

            $form->fill($attributes)->save();

            if (array_key_exists('categories', $data)) {
                $this->syncCategories($form, (array) ($data['categories'] ?? []));
            }

            if (array_key_exists('recipients', $data)) {
                $this->syncRecipients($form, (array) ($data['recipients'] ?? []), $actor);
            } elseif (! $wasExisting && filled($form->recipient_email)) {
                $this->syncRecipients($form, [], $actor);
            }

            if (array_key_exists('messages', $data)) {
                $this->syncMessages($form, (array) ($data['messages'] ?? []), $actor);
            } elseif (! $wasExisting) {
                $this->syncMessages($form, $this->defaultMessages($form), $actor);
            }

            $this->ensureDefaultStructure($form, $actor);

            return $form->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function settings(array $data, array $currentSettings): array
    {
        return [
            'show_title' => array_key_exists('show_title', $data) ? (bool) $data['show_title'] : (bool) ($currentSettings['show_title'] ?? true),
            'layout' => $data['layout'] ?? ($currentSettings['layout'] ?? 'default'),
            'mail_template' => $data['mail_template'] ?? ($currentSettings['mail_template'] ?? 'mail.forms.submission'),
            'store_submissions' => array_key_exists('store_submissions', $data) ? (bool) $data['store_submissions'] : (bool) ($currentSettings['store_submissions'] ?? true),
            'confirmation_email_field' => $data['confirmation_email_field'] ?? ($currentSettings['confirmation_email_field'] ?? null),
            'redirect_url' => $data['redirect_url'] ?? ($currentSettings['redirect_url'] ?? null),
            'from_email' => $data['from_email'] ?? ($currentSettings['from_email'] ?? null),
            'from_name' => $data['from_name'] ?? ($currentSettings['from_name'] ?? null),
        ];
    }

    /**
     * @param  array<int|string, mixed>  $categoryIds
     */
    private function syncCategories(Form $form, array $categoryIds): void
    {
        $ids = collect($categoryIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $form->categories()->sync(
            $ids->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index + 1]])->all()
        );
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $recipients
     */
    private function syncRecipients(Form $form, array $recipients, ?Authenticatable $actor): void
    {
        $seenIds = [];

        if ($recipients === [] && filled($form->recipient_email)) {
            $recipients[] = [
                'name' => __('Primary recipient'),
                'email' => $form->recipient_email,
                'type' => 'to',
                'is_active' => true,
            ];
        }

        foreach (array_values($recipients) as $index => $row) {
            $id = (int) ($row['id'] ?? 0);
            $recipient = $id > 0 ? $form->recipients()->whereKey($id)->first() : null;

            if (! empty($row['delete'])) {
                $recipient?->delete();

                continue;
            }

            if (blank($row['email'] ?? null)) {
                continue;
            }

            $recipient ??= new FormRecipient([
                'form_id' => $form->id,
                'created_by' => $actor?->getAuthIdentifier(),
            ]);
            $recipient->fill([
                'name' => $row['name'] ?? null,
                'email' => $row['email'],
                'type' => $row['type'] ?? 'to',
                'is_active' => (bool) ($row['is_active'] ?? true),
                'sort_order' => (int) ($row['sort_order'] ?? ($index + 1)),
                'settings' => [
                    'conditions' => $row['conditions'] ?? null,
                ],
                'updated_by' => $actor?->getAuthIdentifier(),
            ])->save();

            $seenIds[] = $recipient->id;
        }

        if ($seenIds !== []) {
            $form->recipients()->whereNotIn('id', $seenIds)->delete();
        }
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $messages
     */
    private function syncMessages(Form $form, array $messages, ?Authenticatable $actor): void
    {
        $seenIds = [];

        if ($messages === []) {
            $messages = $this->defaultMessages($form);
        }

        foreach (array_values($messages) as $index => $row) {
            $id = (int) ($row['id'] ?? 0);
            $message = $id > 0 ? $form->messages()->whereKey($id)->first() : null;

            if (! empty($row['delete'])) {
                $message?->delete();

                continue;
            }

            if (blank($row['subject'] ?? null) && blank($row['body'] ?? null)) {
                continue;
            }

            $message ??= new FormMessage([
                'form_id' => $form->id,
                'created_by' => $actor?->getAuthIdentifier(),
            ]);
            $message->fill([
                'name' => $row['name'] ?? null,
                'subject' => $row['subject'] ?? null,
                'body' => $row['body'] ?? null,
                'type' => $row['type'] ?? 'notification',
                'is_active' => (bool) ($row['is_active'] ?? true),
                'sort_order' => (int) ($row['sort_order'] ?? ($index + 1)),
                'settings' => [
                    'layout' => $row['layout'] ?? null,
                ],
                'updated_by' => $actor?->getAuthIdentifier(),
            ])->save();

            $seenIds[] = $message->id;
        }

        if ($seenIds !== []) {
            $form->messages()->whereNotIn('id', $seenIds)->delete();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defaultMessages(Form $form): array
    {
        return [
            [
                'name' => __('Admin notification'),
                'subject' => __('New form submission: :form', ['form' => $form->name]),
                'body' => __('A new submission was received for :form.', ['form' => $form->name])."\n\n{{summary}}",
                'type' => 'notification',
                'is_active' => true,
            ],
            [
                'name' => __('Submitter confirmation'),
                'subject' => __('We received your message'),
                'body' => __('Thank you. We received your message and will respond where needed.')."\n\n{{summary}}",
                'type' => 'confirmation',
                'is_active' => false,
            ],
        ];
    }

    private function ensureDefaultStructure(Form $form, ?Authenticatable $actor): void
    {
        if ($form->blocks()->exists()) {
            return;
        }

        $block = $form->blocks()->create([
            'title' => __('General'),
            'sort_order' => 1,
            'created_by' => $actor?->getAuthIdentifier(),
            'updated_by' => $actor?->getAuthIdentifier(),
        ]);

        $block->rows()->create([
            'sort_order' => 1,
            'created_by' => $actor?->getAuthIdentifier(),
            'updated_by' => $actor?->getAuthIdentifier(),
        ]);
    }
}
