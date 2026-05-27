@php
    $children = $categoriesByParent->get($parentId, collect());
@endphp

@if ($children->isNotEmpty())
    <ul class="category-tree-list" data-drag-container data-drag-layout="list" data-livewire-sort-method="moveCategory" role="{{ $parentId === 0 ? 'tree' : 'group' }}">
        @foreach ($children as $treeCategory)
            <li
                class="category-tree-item"
                data-drag-item
                data-drag-id="{{ $treeCategory->id }}"
                role="treeitem"
                aria-selected="{{ $selectedCategoryId === $treeCategory->id ? 'true' : 'false' }}"
                wire:key="category-tree-node-{{ $module }}-{{ $treeCategory->id }}"
            >
                <div class="category-tree-row {{ $selectedCategoryId === $treeCategory->id ? 'is-selected' : '' }}">
                    <span
                        class="category-tree-drag-handle admin-symbol admin-symbol-reorder"
                        draggable="true"
                        title="{{ __('Sleep om te sorteren') }}"
                    ></span>
                    <button class="category-tree-select" type="button" wire:click="selectCategory({{ $treeCategory->id }})">
                        <span class="admin-symbol admin-symbol-folder" aria-hidden="true"></span>
                        <span>{{ $treeCategory->name }}</span>
                    </button>
                    @if ($selectedCategoryId !== $treeCategory->id)
                        <span class="category-tree-status {{ $this->statusClass($treeCategory) }}" title="{{ $this->statusLabel($treeCategory) }}"></span>
                    @endif
                </div>

                @include('livewire.admin.partials.category-tree-manager-node', [
                    'categoriesByParent' => $categoriesByParent,
                    'parentId' => $treeCategory->id,
                    'selectedCategoryId' => $selectedCategoryId,
                ])
            </li>
        @endforeach
    </ul>
@endif
