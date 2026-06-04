<?php

namespace App\Livewire\Admin\Forms;

use App\Models\Cms\Form;
use App\Models\Cms\FormMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ResponseMailBuilder extends Component
{
    public int $formId;

    /**
     * @var list<array<string, mixed>>
     */
    public array $messages = [];

    public ?string $message = null;

    public string $messageLevel = 'success';

    public function mount(int $formId): void
    {
        $this->ensureAuthorized();

        $this->formId = $formId;
        $this->loadMessages();
    }

    public function addMessage(): void
    {
        $this->messages[] = $this->blankMessage(count($this->messages) + 1);
        $this->message = null;
    }

    public function duplicateMessage(int $index): void
    {
        if (! isset($this->messages[$index])) {
            return;
        }

        $message = $this->messages[$index];
        $message['key'] = $this->messageKey();
        $message['id'] = null;
        $message['name'] = trim((string) ($message['name'] ?? '')) !== ''
            ? __('Copy of :name', ['name' => $message['name']])
            : __('Response mail');
        $message['sort_order'] = count($this->messages) + 1;

        $this->messages[] = $message;
        $this->message = null;
    }

    public function removeMessage(int $index): void
    {
        if (! isset($this->messages[$index])) {
            return;
        }

        array_splice($this->messages, $index, 1);
        $this->reindexSortOrders();
        $this->message = null;
    }

    public function moveMessageUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->messages[$index], $this->messages[$index - 1])) {
            return;
        }

        [$this->messages[$index - 1], $this->messages[$index]] = [$this->messages[$index], $this->messages[$index - 1]];
        $this->reindexSortOrders();
    }

    public function moveMessageDown(int $index): void
    {
        if (! isset($this->messages[$index], $this->messages[$index + 1])) {
            return;
        }

        [$this->messages[$index + 1], $this->messages[$index]] = [$this->messages[$index], $this->messages[$index + 1]];
        $this->reindexSortOrders();
    }

    public function save(): void
    {
        $this->ensureAuthorized();

        $data = Validator::make(['messages' => $this->messages], [
            'messages' => ['array'],
            'messages.*.id' => ['nullable', 'integer', 'exists:form_messages,id'],
            'messages.*.name' => ['nullable', 'string', 'max:255'],
            'messages.*.subject' => ['nullable', 'string', 'max:255'],
            'messages.*.body' => ['nullable', 'string'],
            'messages.*.type' => ['nullable', 'string', Rule::in(['notification', 'confirmation', 'internal'])],
            'messages.*.layout' => ['nullable', 'string', Rule::in(['default', 'compact', 'plain'])],
            'messages.*.is_active' => ['boolean'],
        ])->validate();

        DB::transaction(function () use ($data): void {
            $form = $this->form();
            $seenIds = [];

            foreach (array_values($data['messages'] ?? []) as $index => $row) {
                $id = (int) ($row['id'] ?? 0);
                $message = $id > 0 ? $form->messages()->whereKey($id)->first() : null;

                if (blank($row['subject'] ?? null) && blank($row['body'] ?? null)) {
                    continue;
                }

                $message ??= new FormMessage([
                    'form_id' => $form->id,
                    'created_by' => auth()->id(),
                ]);

                $message->fill([
                    'name' => $row['name'] ?? null,
                    'subject' => $row['subject'] ?? null,
                    'body' => $row['body'] ?? null,
                    'type' => $row['type'] ?? 'confirmation',
                    'is_active' => (bool) ($row['is_active'] ?? true),
                    'sort_order' => $index + 1,
                    'settings' => [
                        'layout' => $row['layout'] ?? 'default',
                    ],
                    'updated_by' => auth()->id(),
                ])->save();

                $seenIds[] = $message->id;
            }

            $form->messages()
                ->when($seenIds !== [], fn ($query) => $query->whereNotIn('id', $seenIds))
                ->delete();
        });

        $this->loadMessages();
        $this->messageLevel = 'success';
        $this->message = __('Response mail saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.forms.response-mail-builder', [
            'layoutOptions' => $this->layoutOptions(),
            'placeholderGroups' => $this->placeholderGroups(),
        ]);
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);
    }

    private function form(): Form
    {
        return Form::query()
            ->with(['messages', 'blocks.rows.fields'])
            ->findOrFail($this->formId);
    }

    private function loadMessages(): void
    {
        $this->messages = $this->form()
            ->messages
            ->values()
            ->map(fn (FormMessage $message, int $index): array => [
                'key' => $this->messageKey(),
                'id' => $message->id,
                'name' => $message->name,
                'subject' => $message->subject,
                'body' => $message->body,
                'type' => $message->type ?: 'confirmation',
                'layout' => $message->settings['layout'] ?? 'default',
                'is_active' => (bool) $message->is_active,
                'sort_order' => $message->sort_order ?: $index + 1,
            ])
            ->all();

        if ($this->messages === []) {
            $this->addMessage();
        }
    }

    /**
     * @return array<string, string>
     */
    private function layoutOptions(): array
    {
        return [
            'default' => __('Default'),
            'compact' => __('Compact'),
            'plain' => __('Plain text'),
        ];
    }

    /**
     * @return array<int, array{label: string, tags: array<int, array{token: string, label: string}>}>
     */
    private function placeholderGroups(): array
    {
        $form = $this->form();
        $fields = $form->blocks
            ->flatMap(fn ($block) => $block->rows)
            ->flatMap(fn ($row) => $row->fields)
            ->filter(fn ($field): bool => $field->acceptsSubmissionValue())
            ->values()
            ->map(fn ($field): array => [
                'token' => '{'.$field->name.'}',
                'label' => $field->label ?: $field->name,
            ])
            ->all();

        return [
            [
                'label' => __('Systeemtags'),
                'tags' => [
                    ['token' => '{form_name}', 'label' => __('Formuliernaam')],
                    ['token' => '{summary}', 'label' => __('Samenvatting van inzending')],
                ],
            ],
            [
                'label' => __('Formuliervelden'),
                'tags' => $fields,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankMessage(int $sortOrder): array
    {
        return [
            'key' => $this->messageKey(),
            'id' => null,
            'name' => __('Response mail'),
            'subject' => __('We received your message'),
            'body' => "{{summary}}",
            'type' => 'confirmation',
            'layout' => 'default',
            'is_active' => true,
            'sort_order' => $sortOrder,
        ];
    }

    private function reindexSortOrders(): void
    {
        foreach ($this->messages as $index => $message) {
            $this->messages[$index]['sort_order'] = $index + 1;
        }
    }

    private function messageKey(): string
    {
        return 'response-mail-'.Str::uuid()->toString();
    }
}
