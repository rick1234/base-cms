@php
    $catalogRoutePrefix = request()->routeIs('cms.*') ? 'cms.catalog.' : 'admin.catalog.';
    $catalogToolbarLinks = [
        ['route' => $catalogRoutePrefix.'index', 'icon' => 'inventory_2', 'label' => __('Producten')],
        ['route' => $catalogRoutePrefix.'categories.index', 'icon' => 'folder', 'label' => __('Categorieen')],
        ['route' => $catalogRoutePrefix.'brands.index', 'icon' => 'sell', 'label' => __('Merken')],
        ['route' => $catalogRoutePrefix.'promotions.index', 'icon' => 'campaign', 'label' => __('Promoties')],
        ['route' => $catalogRoutePrefix.'coupons.index', 'icon' => 'local_offer', 'label' => __('Actiecodes')],
        ['route' => $catalogRoutePrefix.'reviews.index', 'icon' => 'reviews', 'label' => __('Reviews')],
    ];
@endphp

@foreach ($catalogToolbarLinks as $toolbarLink)
    @if (\Illuminate\Support\Facades\Route::has($toolbarLink['route']))
        <a class="btn {{ request()->routeIs($toolbarLink['route']) ? 'is-active' : '' }}" href="{{ route($toolbarLink['route']) }}">
            <x-admin.material-icon :name="$toolbarLink['icon']" />
            {{ $toolbarLink['label'] }}
        </a>
    @endif
@endforeach
