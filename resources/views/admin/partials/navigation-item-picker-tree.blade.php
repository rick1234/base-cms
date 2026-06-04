@php
    $children = $navigationItemsByParent->get($parentId ?? 0, collect());
    $selectedNavigationItemId = (int) ($selectedNavigationItemId ?? 0);
    $inputName = $inputName ?? 'navigation_item_id';
@endphp

@if ($children->isNotEmpty())
    <ul class="listing-category-tree" role="{{ ($parentId ?? 0) === 0 ? 'tree' : 'group' }}">
        @foreach ($children as $treeNavigationItem)
            @php
                $treeNavigationItemLabel = trim(($treeNavigationItem->menu?->name && blank($treeNavigationItem->parent_id) ? $treeNavigationItem->menu->name.' / ' : '').$treeNavigationItem->title);
            @endphp
            <li role="treeitem" aria-selected="{{ $selectedNavigationItemId === $treeNavigationItem->id ? 'true' : 'false' }}">
                <label class="listing-category-option {{ $selectedNavigationItemId === $treeNavigationItem->id ? 'is-selected' : '' }}">
                    <input
                        type="radio"
                        name="{{ $inputName }}"
                        value="{{ $treeNavigationItem->id }}"
                        @checked($selectedNavigationItemId === $treeNavigationItem->id)
                    >
                    <x-admin.material-icon name="link" />
                    <span>{{ $treeNavigationItemLabel }}</span>
                </label>

                @include('admin.partials.navigation-item-picker-tree', [
                    'navigationItemsByParent' => $navigationItemsByParent,
                    'parentId' => $treeNavigationItem->id,
                    'selectedNavigationItemId' => $selectedNavigationItemId,
                    'inputName' => $inputName,
                ])
            </li>
        @endforeach
    </ul>
@endif
