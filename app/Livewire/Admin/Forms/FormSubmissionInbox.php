<?php

namespace App\Livewire\Admin\Forms;

use App\Models\Cms\Form;
use App\Models\Cms\FormSubmission;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class FormSubmissionInbox extends Component
{
    use WithPagination;

    public int $formId;

    public ?int $selectedSubmissionId = null;

    public int $perPage = 25;

    /**
     * @var array<string, mixed>
     */
    protected array $queryString = [
        'selectedSubmissionId' => ['as' => 'message', 'except' => null],
    ];

    public function mount(Form $form): void
    {
        $this->formId = $form->id;

        if ($this->selectedSubmissionId !== null && ! $this->submissionBelongsToForm($this->selectedSubmissionId)) {
            $this->selectedSubmissionId = null;
        }

        $this->selectedSubmissionId ??= $this->latestSubmissionId();
    }

    public function selectSubmission(int $submissionId): void
    {
        if (! $this->submissionBelongsToForm($submissionId)) {
            return;
        }

        $this->selectedSubmissionId = $submissionId;
    }

    public function render(): View
    {
        $submissions = $this->submissions();
        $selectedSubmission = $this->selectedSubmission();

        if (! $selectedSubmission && $submissions->count() > 0) {
            $this->selectedSubmissionId = $submissions->first()?->id;
            $selectedSubmission = $this->selectedSubmission();
        }

        return view('livewire.admin.forms.form-submission-inbox', [
            'submissions' => $submissions,
            'selectedSubmission' => $selectedSubmission,
        ]);
    }

    public function submissionTitle(FormSubmission $submission): string
    {
        $answers = $submission->answers;
        $title = $answers->first(fn ($answer): bool => in_array(strtolower((string) $answer->field_name), ['subject', 'onderwerp', 'title', 'titel'], true))?->value
            ?: $answers->first(fn ($answer): bool => in_array(strtolower((string) $answer->field_name), ['name', 'naam', 'email'], true))?->value;

        return trim((string) $title) !== '' ? (string) $title : __('Message #:id', ['id' => $submission->id]);
    }

    public function senderText(FormSubmission $submission): string
    {
        $sender = $submission->answers->first(fn ($answer): bool => in_array(strtolower((string) $answer->field_name), ['name', 'naam'], true))?->value
            ?: $submission->answers->first(fn ($answer): bool => strtolower((string) $answer->field_name) === 'email')?->value;

        return trim((string) $sender) !== '' ? (string) $sender : __('Unknown sender');
    }

    public function previewText(FormSubmission $submission): string
    {
        $preview = $submission->answers
            ->pluck('value')
            ->map(fn (mixed $value): string => trim(preg_replace('/\s+/', ' ', (string) $value) ?? ''))
            ->filter()
            ->take(2)
            ->join(' - ');

        return $preview !== '' ? $preview : ($submission->source_url ?: __('No preview available'));
    }

    /**
     * @return LengthAwarePaginator<int, FormSubmission>
     */
    private function submissions(): LengthAwarePaginator
    {
        return FormSubmission::query()
            ->where('form_id', $this->formId)
            ->with('answers')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($this->perPage);
    }

    private function selectedSubmission(): ?FormSubmission
    {
        if ($this->selectedSubmissionId === null) {
            return null;
        }

        return FormSubmission::query()
            ->where('form_id', $this->formId)
            ->with('answers')
            ->find($this->selectedSubmissionId);
    }

    private function latestSubmissionId(): ?int
    {
        return FormSubmission::query()
            ->where('form_id', $this->formId)
            ->latest('created_at')
            ->latest('id')
            ->value('id');
    }

    private function submissionBelongsToForm(int $submissionId): bool
    {
        return FormSubmission::query()
            ->where('form_id', $this->formId)
            ->whereKey($submissionId)
            ->exists();
    }
}
