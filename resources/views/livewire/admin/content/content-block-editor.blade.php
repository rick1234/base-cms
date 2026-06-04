<div
    class="content-block-editor"
    data-content-block-editor
    data-content-block-auto-save-error="{{ __('Content blocks could not be saved automatically. Save the blocks and try again.') }}"
>
    @if ($message)
        <div class="flash-message flash-message-success" data-flash-message>
            <span>{{ $message }}</span>
            <button class="flash-message-close" type="button" data-flash-close aria-label="{{ __('Close') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    @if ($usesLegacySource)
        <div class="content-block-editor-notice">
            {{ __('Legacy blocks are shown as converted structured blocks. Save the blocks once to store the new JSON version and keep a legacy snapshot.') }}
        </div>
    @endif

    <div class="content-block-editor-toolbar">
        <button class="btn btn-save" type="button" wire:click="save" wire:loading.attr="disabled" data-content-block-editor-save>
            <x-admin.material-icon name="save" />
            {{ __('Save blocks') }}
        </button>
        <span class="content-block-editor-status" wire:loading wire:target="save">
            {{ __('Saving...') }}
        </span>
    </div>

    <div class="content-block-builder" data-content-block-builder>
        {{ $this->form }}
    </div>

    <x-filament-actions::modals />
</div>
