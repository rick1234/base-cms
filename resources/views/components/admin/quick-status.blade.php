@props([
    'model',
    'record',
    'value' => null,
    'active' => false,
    'statuses' => null,
])

@php
    $statusSets = [
        'publishing' => [
            'published' => ['label' => __('Online'), 'class' => 'is-published'],
            'draft' => ['label' => __('Offline'), 'class' => 'is-draft'],
            'archived' => ['label' => __('Archived'), 'class' => 'is-archived'],
        ],
        'review' => [
            'pending' => ['label' => __('Pending'), 'class' => 'is-pending'],
            'published' => ['label' => __('Published'), 'class' => 'is-published'],
            'rejected' => ['label' => __('Rejected'), 'class' => 'is-rejected'],
        ],
        'active' => [
            'active' => ['label' => __('Actief'), 'class' => 'is-active'],
            'inactive' => ['label' => __('Inactief'), 'class' => 'is-inactive'],
        ],
        'boolean_active' => [
            'active' => ['label' => __('Active'), 'class' => 'is-active'],
            'inactive' => ['label' => __('Inactive'), 'class' => 'is-inactive'],
        ],
        'enabled' => [
            'enabled' => ['label' => __('Enabled'), 'class' => 'is-active'],
            'disabled' => ['label' => __('Disabled'), 'class' => 'is-inactive'],
        ],
    ];

    $modelStatusSets = [
        'banner' => 'publishing',
        'catalog-brand' => 'active',
        'catalog-coupon' => 'boolean_active',
        'catalog-product' => 'publishing',
        'catalog-promotion' => 'active',
        'catalog-review' => 'review',
        'content' => 'publishing',
        'country' => 'active',
        'country-enabled' => 'enabled',
        'download' => 'active',
        'domain' => 'boolean_active',
        'event' => 'publishing',
        'faq' => 'publishing',
        'form' => 'publishing',
        'language' => 'active',
        'location' => 'active',
        'navigation-menu' => 'boolean_active',
        'redirect' => 'boolean_active',
        'role' => 'active',
        'user' => 'boolean_active',
        'vacancy' => 'publishing',
        'website-template' => 'boolean_active',
    ];

    $statusOptions = $statuses ?? $statusSets[$modelStatusSets[$model] ?? 'active'];
    $currentValue = is_bool($value) ? ($value ? 'active' : 'inactive') : (string) $value;
    $currentLabel = $statusOptions[$currentValue]['label'] ?? ($active ? __('Online') : __('Offline'));
    $routeName = request()->routeIs('cms.*') ? 'cms.quick-status.update' : 'admin.quick-status.update';
    $dialogId = 'quick-status-'.$model.'-'.$record->getKey();
@endphp

<details class="quick-status">
    <summary class="quick-status-trigger" aria-label="{{ __('Change status for :item', ['item' => $currentLabel]) }}" title="{{ __('Change status') }}">
        <span class="{{ $active ? 'active-item' : 'inactive-item' }}" aria-hidden="true"></span>
    </summary>
    <button class="quick-status-backdrop" type="button" aria-label="{{ __('Close') }}" data-quick-status-close></button>
    <div class="quick-status-modal" role="dialog" aria-modal="true" aria-labelledby="{{ $dialogId }}-title">
        <div class="quick-status-modal-panel">
            <div class="quick-status-modal-header">
                <div class="quick-status-modal-title">
                    <h2 id="{{ $dialogId }}-title">{{ __('Change status') }}</h2>
                    <span>{{ __('Available statuses') }}</span>
                </div>
                <button class="quick-status-modal-close" type="button" aria-label="{{ __('Close') }}" data-quick-status-close>
                    <x-admin.material-icon name="close" />
                </button>
            </div>
            <form class="quick-status-options" method="post" action="{{ route($routeName) }}">
                @csrf
                @method('patch')
                <input type="hidden" name="model" value="{{ $model }}">
                <input type="hidden" name="id" value="{{ $record->getKey() }}">

                @foreach ($statusOptions as $statusValue => $status)
                    <button class="quick-status-option {{ $currentValue === $statusValue ? 'is-selected' : '' }}" type="submit" name="status" value="{{ $statusValue }}">
                        <span class="quick-status-dot {{ $status['class'] }}" aria-hidden="true"></span>
                        <span>{{ $status['label'] }}</span>
                    </button>
                @endforeach
            </form>
        </div>
    </div>
</details>
