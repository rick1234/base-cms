@props([
    'domain',
    'activeStep',
    'previousStep',
    'nextStep',
])

<div class="domain-tab-actions">
    @if ($domain->exists && $previousStep !== $activeStep)
        <button class="btn btn-save" name="_next_step" type="submit" value="{{ $previousStep }}">
            <x-admin.material-icon name="arrow_back" />
            {{ __('Save and go back') }}
        </button>
    @endif

    @if ($activeStep === 'review')
        <button class="btn btn-save" name="_next_step" type="submit" value="review">
            <x-admin.material-icon name="save" />
            {{ __('Finish') }}
        </button>
    @else
        <button class="btn btn-save" name="_next_step" type="submit" value="{{ $activeStep }}">
            <x-admin.material-icon name="save" />
            {{ __('Save step') }}
        </button>
        <button class="btn btn-save" name="_next_step" type="submit" value="{{ $nextStep }}">
            <x-admin.material-icon name="save" />
            {{ __('Save and continue') }}
        </button>
    @endif
</div>
