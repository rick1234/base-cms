@php
    $blockData = $get('data');
    $blockSettings = $get('settings');

    $blockData = is_array($blockData) ? $blockData : [];
    $blockSettings = is_array($blockSettings) ? $blockSettings : [];
@endphp

<div class="content-block-inline-preview" data-content-block-inline-preview>
    <div class="content-block-inline-preview-body">
        <span class="content-block-inline-preview-label">{{ $blockLabel }}</span>

        @include($previewView, [
            'data' => $blockData,
            'settings' => $blockSettings,
        ])
    </div>

    <button
        class="content-block-inline-edit-button"
        type="button"
        data-content-block-edit-toggle
        aria-label="{{ __('Edit block content') }}"
    >
        <x-admin.material-icon name="edit" />
    </button>
</div>
