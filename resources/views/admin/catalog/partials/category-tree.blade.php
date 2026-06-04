@include('admin.partials.category-tree', [
    'categoriesByParent' => $categoriesByParent,
    'parentId' => $parentId ?? 0,
    'selectedCategoryId' => $selectedCategoryId ?? null,
    'selectedParentId' => $selectedParentId ?? null,
    'currentCategoryId' => $currentCategoryId ?? null,
    'linkedIds' => $linkedIds ?? [],
    'mode' => $mode ?? 'browse',
    'routeNames' => $routeNames,
    'countAttribute' => 'products_count',
])
