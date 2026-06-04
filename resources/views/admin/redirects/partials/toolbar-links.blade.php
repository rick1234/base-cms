@php
    $usesLegacyRoutes = request()->routeIs('cms.*');
    $moduleShowRoute = $usesLegacyRoutes ? 'cms.modules.index' : 'admin.modules.show';
    $activeSeoModule = $activeSeoModule ?? ($module['folder'] ?? null);
    $seoToolbarLinks = [
        [
            'url' => route($usesLegacyRoutes ? 'cms.redirects.index' : 'admin.redirects.index'),
            'icon' => 'route',
            'label' => __('Redirects'),
            'active' => request()->routeIs('admin.redirects.*') || request()->routeIs('cms.redirects.*'),
        ],
        [
            'url' => route($moduleShowRoute, 'url'),
            'icon' => 'link',
            'label' => __('URLs'),
            'active' => $activeSeoModule === 'url',
        ],
        [
            'url' => route($moduleShowRoute, 'urlverwijzingen'),
            'icon' => 'call_split',
            'label' => __('URL references'),
            'active' => $activeSeoModule === 'urlverwijzingen',
        ],
    ];
@endphp

@foreach ($seoToolbarLinks as $toolbarLink)
    <a class="btn {{ $toolbarLink['active'] ? 'is-active' : '' }}" href="{{ $toolbarLink['url'] }}">
        <x-admin.material-icon :name="$toolbarLink['icon']" />
        {{ $toolbarLink['label'] }}
    </a>
@endforeach
