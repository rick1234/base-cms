<div class="form-submission-inbox" wire:loading.class="is-loading">
    <aside class="form-submission-inbox-list" aria-label="{{ __('Form messages') }}">
        <header class="form-submission-inbox-list-header">
            <span>{{ __('Inbox') }}</span>
            <span>{{ trans_choice(':count message|:count messages', $submissions->total(), ['count' => $submissions->total()]) }}</span>
        </header>

        <div class="form-submission-inbox-items">
            @forelse ($submissions as $submission)
                <button
                    type="button"
                    wire:key="form-submission-{{ $submission->id }}"
                    wire:click="selectSubmission({{ $submission->id }})"
                    @class([
                        'form-submission-inbox-item',
                        'is-selected' => $selectedSubmission?->id === $submission->id,
                    ])
                >
                    <span class="form-submission-inbox-item-topline">
                        <span class="form-submission-inbox-sender">{{ $this->senderText($submission) }}</span>
                        <time datetime="{{ optional($submission->created_at)->toIso8601String() }}">
                            {{ optional($submission->created_at)->format('d-m-Y H:i') }}
                        </time>
                    </span>
                    <span class="form-submission-inbox-subject">{{ $this->submissionTitle($submission) }}</span>
                    <span class="form-submission-inbox-preview">{{ $this->previewText($submission) }}</span>
                    <span class="form-submission-inbox-meta">
                        <span>{{ $submission->status ?: __('New') }}</span>
                        <span>{{ $submission->source_url ?: __('No source') }}</span>
                    </span>
                </button>
            @empty
                <div class="form-submission-inbox-empty">
                    <x-admin.material-icon name="info" />
                    <em>{{ __('No messages found.') }}</em>
                </div>
            @endforelse
        </div>

        @if ($submissions->hasPages())
            <nav class="admin-pagination form-submission-inbox-pagination" aria-label="{{ __('Pagination') }}">
                <ul class="admin-pagination-list">
                    @if ($submissions->onFirstPage())
                        <li><span class="admin-pagination-disabled" aria-hidden="true">&lsaquo;</span></li>
                    @else
                        <li><button class="admin-pagination-link" type="button" wire:click="previousPage" rel="prev" aria-label="{{ __('Previous') }}">&lsaquo;</button></li>
                    @endif

                    <li><span class="admin-pagination-current" aria-current="page">{{ $submissions->currentPage() }}</span></li>

                    @if ($submissions->hasMorePages())
                        <li><button class="admin-pagination-link" type="button" wire:click="nextPage" rel="next" aria-label="{{ __('Next') }}">&rsaquo;</button></li>
                    @else
                        <li><span class="admin-pagination-disabled" aria-hidden="true">&rsaquo;</span></li>
                    @endif
                </ul>
            </nav>
        @endif
    </aside>

    <section class="form-submission-reading-pane" aria-label="{{ __('Message preview') }}">
        @if ($selectedSubmission)
            <header class="form-submission-reading-header">
                <div>
                    <p class="form-submission-reading-kicker">{{ __('Form message') }}</p>
                    <h2>{{ $this->submissionTitle($selectedSubmission) }}</h2>
                </div>
                <span class="form-submission-reading-status">{{ $selectedSubmission->status ?: __('New') }}</span>
            </header>

            <dl class="form-submission-reading-meta">
                <div>
                    <dt>{{ __('Received') }}</dt>
                    <dd>{{ optional($selectedSubmission->created_at)->format('d-m-Y H:i') ?: '-' }}</dd>
                </div>
                <div>
                    <dt>{{ __('Sender') }}</dt>
                    <dd>{{ $this->senderText($selectedSubmission) }}</dd>
                </div>
                <div>
                    <dt>{{ __('Source') }}</dt>
                    <dd>{{ $selectedSubmission->source_url ?: '-' }}</dd>
                </div>
                <div>
                    <dt>{{ __('IP') }}</dt>
                    <dd>{{ $selectedSubmission->remote_ip ?: '-' }}</dd>
                </div>
                <div class="form-submission-reading-meta-wide">
                    <dt>{{ __('Browser') }}</dt>
                    <dd>{{ $selectedSubmission->user_agent ?: '-' }}</dd>
                </div>
            </dl>

            <div class="form-submission-reading-answers">
                @foreach ($selectedSubmission->answers as $answer)
                    <article class="form-submission-reading-answer">
                        <h3>{{ $answer->field_name ?: __('Answer') }}</h3>
                        <p>{{ trim((string) $answer->value) !== '' ? $answer->value : '-' }}</p>
                    </article>
                @endforeach
            </div>
        @else
            <div class="form-submission-reading-empty">
                <x-admin.material-icon name="mail" />
                <p>{{ __('Select a message to read it.') }}</p>
            </div>
        @endif
    </section>
</div>
