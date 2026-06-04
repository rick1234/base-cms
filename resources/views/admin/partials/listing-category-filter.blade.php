@php
    $inputName = $inputName ?? 'categoryId';
    $showChildName = $showChildName ?? 'showChild';
    $selectedCategoryId = (int) ($selectedCategoryId ?? 0);
    $selectedCategoryName = $selectedCategoryId > 0
        ? (string) ($categories->firstWhere('id', $selectedCategoryId)?->name ?? __('Onbekende categorie'))
        : __('Selecteer');
    $categoriesByParent = $categoriesByParent ?? $categories->groupBy(fn ($category) => $category->parent_id ?: 0);
@endphp

<div class="listing-category-picker">
    <details class="listing-category-native">
        <summary class="listing-category-picker-button">
            <x-admin.material-icon name="folder" />
            <span>{{ $selectedCategoryName }}</span>
        </summary>
        <div class="listing-category-native-panel listing-category-modal-body">
            <div class="listing-category-modal-options">
                <label class="listing-filter-check">
                    <input type="checkbox" name="{{ $showChildName }}" value="1" @checked($showChild ?? false)>
                    <span>{{ __('Ook onderliggende') }}</span>
                </label>
            </div>

            <label class="listing-category-option {{ $selectedCategoryId === 0 ? 'is-selected' : '' }}">
                <input type="radio" name="{{ $inputName }}" value="0" @checked($selectedCategoryId === 0)>
                <x-admin.material-icon name="info" />
                <span>{{ __('Alle categorieen') }}</span>
            </label>

            @include('admin.partials.category-filter-tree', [
                'categoriesByParent' => $categoriesByParent,
                'parentId' => 0,
                'selectedCategoryId' => $selectedCategoryId,
                'inputName' => $inputName,
                'mode' => 'radio',
            ])
        </div>
    </details>

    @if ($selectedCategoryId > 0)
        <a class="listing-category-clear-button" href="{{ $clearUrl ?? request()->fullUrlWithQuery([$inputName => null, $showChildName => null]) }}" title="{{ __('Categorie wissen') }}">
            <x-admin.material-icon name="close" />
        </a>
    @endif
</div>
