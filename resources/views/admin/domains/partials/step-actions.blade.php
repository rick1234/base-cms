@props([
    'domain',
    'activeStep',
    'previousStep',
    'nextStep',
])

<div class="domain-wizard-submit">
    @if ($domain->exists && $previousStep !== $activeStep)
        <button class="btn" name="_next_step" type="submit" value="{{ $previousStep }}">
            <span class="flaticon-back-arrow"></span>
            {{ __('Save and go back') }}
        </button>
    @endif

    @if ($activeStep === 'review')
        <button class="btn btn-add" name="_next_step" type="submit" value="review">
            <span class="flaticon-save-file-option"></span>
            {{ __('Finish') }}
        </button>
    @else
        <button class="btn btn-add" name="_next_step" type="submit" value="{{ $activeStep }}">
            <span class="flaticon-save-file-option"></span>
            {{ __('Save step') }}
        </button>
        <button class="btn btn-add" name="_next_step" type="submit" value="{{ $nextStep }}">
            <span class="flaticon-save-file-option"></span>
            {{ __('Save and continue') }}
        </button>
    @endif
</div>
