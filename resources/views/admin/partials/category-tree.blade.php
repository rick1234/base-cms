@php
    $children = $categoriesByParent->get($parentId ?? 0, collect());
    $linkedIds = collect($linkedIds ?? []);
    $mode = $mode ?? 'browse';
    $categoryInputName = $categoryInputName ?? 'categories[]';
    $parentInputName = $parentInputName ?? 'parent_id';
@endphp

@if ($children->isNotEmpty())
    <ul class="content-category-tree">
        @foreach ($children as $treeCategory)
            <li>
                @if ($mode === 'select')
                    <label>
                        <input
                            type="checkbox"
                            name="{{ $categoryInputName }}"
                            value="{{ $treeCategory->id }}"
                            @checked($linkedIds->contains($treeCategory->id))
                        >
                        <span class="checkbox"></span>
                        {{ $treeCategory->name }}
                    </label>
                @elseif ($mode === 'parent')
                    <label>
                        <input
                            type="radio"
                            name="{{ $parentInputName }}"
                            value="{{ $treeCategory->id }}"
                            @checked((int) old($parentInputName, $selectedParentId ?? null) === $treeCategory->id)
                            @disabled(($currentCategoryId ?? null) === $treeCategory->id)
                        >
                        <span class="checkbox"></span>
                        {{ $treeCategory->name }}
                    </label>
                @else
                    <a
                        class="{{ ($selectedCategoryId ?? null) === $treeCategory->id ? 'active' : '' }}"
                        href="{{ route($routeNames['index'], ['categoryId' => $treeCategory->id]) }}"
                    >
                        {{ $treeCategory->name }}
                    </a>
                    @if ($countAttribute ?? null)
                        <span class="content-category-count">{{ $treeCategory->{$countAttribute} ?? 0 }}</span>
                    @endif
                @endif

                @include('admin.partials.category-tree', [
                    'categoriesByParent' => $categoriesByParent,
                    'parentId' => $treeCategory->id,
                    'selectedCategoryId' => $selectedCategoryId ?? null,
                    'selectedParentId' => $selectedParentId ?? null,
                    'currentCategoryId' => $currentCategoryId ?? null,
                    'linkedIds' => $linkedIds,
                    'mode' => $mode,
                    'routeNames' => $routeNames,
                    'countAttribute' => $countAttribute ?? null,
                    'categoryInputName' => $categoryInputName,
                    'parentInputName' => $parentInputName,
                ])
            </li>
        @endforeach
    </ul>
@endif
