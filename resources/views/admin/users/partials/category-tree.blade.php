@php
    $children = $categoriesByParent->get($parentId, collect());
    $linkedIds = collect($linkedIds ?? []);
@endphp

@if ($children->isNotEmpty())
    <ul class="content-category-tree">
        @foreach ($children as $treeCategory)
            <li>
                @if (($mode ?? 'browse') === 'select')
                    <label>
                        <input
                            type="checkbox"
                            name="categories[]"
                            value="{{ $treeCategory->id }}"
                            @checked($linkedIds->contains($treeCategory->id))
                        >
                        <span class="checkbox"></span>
                        {{ $treeCategory->name }}
                    </label>
                @elseif (($mode ?? 'browse') === 'parent')
                    <label>
                        <input
                            type="radio"
                            name="parent_id"
                            value="{{ $treeCategory->id }}"
                            @checked((int) old('parent_id', $selectedParentId ?? null) === $treeCategory->id)
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
                    <span class="content-category-count">{{ $treeCategory->users_count }}</span>
                @endif

                @include('admin.users.partials.category-tree', [
                    'categoriesByParent' => $categoriesByParent,
                    'parentId' => $treeCategory->id,
                    'selectedCategoryId' => $selectedCategoryId ?? null,
                    'selectedParentId' => $selectedParentId ?? null,
                    'currentCategoryId' => $currentCategoryId ?? null,
                    'linkedIds' => $linkedIds,
                    'mode' => $mode ?? 'browse',
                    'routeNames' => $routeNames,
                ])
            </li>
        @endforeach
    </ul>
@endif
