@php
    $inputName = $inputName ?? 'navigation_item_id';
    $selectedNavigationItemId = (int) ($selectedNavigationItemId ?? 0);
    $emptyLabel = $emptyLabel ?? __('Geen extra link');
    $navigationItemsByParent = $navigationItemsByParent ?? $navigationItems->groupBy(fn ($item) => $item->parent_id ?: 0);
    $selectedNavigationItem = $selectedNavigationItemId > 0 ? $navigationItems->firstWhere('id', $selectedNavigationItemId) : null;
    $selectedNavigationItemName = $selectedNavigationItem
        ? trim(($selectedNavigationItem->menu?->name ? $selectedNavigationItem->menu->name.' / ' : '').$selectedNavigationItem->title)
        : $emptyLabel;
@endphp

<div class="listing-category-picker">
    <details class="listing-category-native">
        <summary class="listing-category-picker-button">
            <x-admin.material-icon name="link" />
            <span>{{ $selectedNavigationItemName }}</span>
        </summary>
        <div class="listing-category-native-panel listing-category-modal-body">
            <label class="listing-category-option {{ $selectedNavigationItemId === 0 ? 'is-selected' : '' }}">
                <input type="radio" name="{{ $inputName }}" value="" @checked($selectedNavigationItemId === 0)>
                <x-admin.material-icon name="link_off" />
                <span>{{ $emptyLabel }}</span>
            </label>

            @include('admin.partials.navigation-item-picker-tree', [
                'navigationItemsByParent' => $navigationItemsByParent,
                'parentId' => 0,
                'selectedNavigationItemId' => $selectedNavigationItemId,
                'inputName' => $inputName,
            ])
        </div>
    </details>
</div>
