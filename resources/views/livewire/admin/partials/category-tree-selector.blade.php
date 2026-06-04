@include('admin.partials.category-filter-tree', [
    'categoriesByParent' => $categoriesByParent,
    'parentId' => $parentId,
    'selectedCategoryId' => $draftCategoryId,
    'mode' => 'livewire',
    'livewireAction' => 'selectCategory',
])
