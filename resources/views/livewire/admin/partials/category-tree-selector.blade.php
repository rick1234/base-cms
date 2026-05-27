@php
    $children = $categoriesByParent->get($parentId, collect());
@endphp

@if ($children->isNotEmpty())
    <ul class="listing-category-tree" role="{{ $parentId === 0 ? 'tree' : 'group' }}">
        @foreach ($children as $treeCategory)
            <li role="treeitem" aria-selected="{{ $draftCategoryId === $treeCategory->id ? 'true' : 'false' }}">
                <button
                    class="listing-category-option {{ $draftCategoryId === $treeCategory->id ? 'is-selected' : '' }}"
                    type="button"
                    wire:click="selectCategory({{ $treeCategory->id }})"
                >
                    <span class="admin-symbol admin-symbol-folder" aria-hidden="true"></span>
                    <span>{{ $treeCategory->name }}</span>
                </button>

                @include('livewire.admin.partials.category-tree-selector', [
                    'categoriesByParent' => $categoriesByParent,
                    'parentId' => $treeCategory->id,
                    'draftCategoryId' => $draftCategoryId,
                ])
            </li>
        @endforeach
    </ul>
@endif
