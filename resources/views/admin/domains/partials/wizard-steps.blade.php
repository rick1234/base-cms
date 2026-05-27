@props([
    'domain',
    'steps' => [],
    'stepCompletion' => [],
    'activeStep' => 'identity',
    'formId' => 'domain-form',
])

<nav class="domain-wizard" aria-label="{{ __('Domain setup steps') }}">
    <ol class="domain-wizard-list">
        @foreach ($steps as $key => $step)
            @php
                $complete = (bool) ($stepCompletion[$key] ?? false);
                $state = $key === $activeStep ? 'active' : ($complete ? 'complete' : 'pending');
                $canNavigate = $domain->exists;
            @endphp
            <li class="domain-wizard-item domain-wizard-item--{{ $state }}">
                <button
                    class="domain-wizard-link"
                    form="{{ $formId }}"
                    name="_next_step"
                    type="submit"
                    value="{{ $key }}"
                    @disabled(! $canNavigate)
                    @if ($key === $activeStep) aria-current="step" @endif
                >
                    <span class="domain-wizard-status" aria-hidden="true">
                        @if ($complete)
                            <x-admin.material-icon name="check_circle" />
                        @else
                            <x-admin.material-icon name="cancel" />
                        @endif
                    </span>
                    <span class="domain-wizard-label">{{ __($step['label']) }}</span>
                </button>
            </li>
        @endforeach
    </ol>
</nav>
