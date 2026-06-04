@php
    $children = $categoriesByParent->get($parentId ?? 0, collect());
    $selectedCategoryId = (int) ($selectedCategoryId ?? 0);
    $mode = $mode ?? 'radio';
    $inputName = $inputName ?? 'categoryId';
    $livewireAction = $livewireAction ?? 'selectCategory';
@endphp

@if ($children->isNotEmpty())
    <ul class="listing-category-tree" role="{{ ($parentId ?? 0) === 0 ? 'tree' : 'group' }}">
        @foreach ($children as $treeCategory)
            <li role="treeitem" aria-selected="{{ $selectedCategoryId === $treeCategory->id ? 'true' : 'false' }}">
                @if ($mode === 'livewire')
                    <button
                        class="listing-category-option {{ $selectedCategoryId === $treeCategory->id ? 'is-selected' : '' }}"
                        type="button"
                        wire:click="{{ $livewireAction }}({{ $treeCategory->id }})"
                    >
                        <x-admin.material-icon name="folder" />
                        <span>{{ $treeCategory->name }}</span>
                    </button>
                @else
                    <label class="listing-category-option {{ $selectedCategoryId === $treeCategory->id ? 'is-selected' : '' }}">
                        <input
                            type="radio"
                            name="{{ $inputName }}"
                            value="{{ $treeCategory->id }}"
                            @checked($selectedCategoryId === $treeCategory->id)
                        >
                        <x-admin.material-icon name="folder" />
                        <span>{{ $treeCategory->name }}</span>
                    </label>
                @endif

                @include('admin.partials.category-filter-tree', [
                    'categoriesByParent' => $categoriesByParent,
                    'parentId' => $treeCategory->id,
                    'selectedCategoryId' => $selectedCategoryId,
                    'mode' => $mode,
                    'inputName' => $inputName,
                    'livewireAction' => $livewireAction,
                ])
            </li>
        @endforeach
    </ul>
@endif
