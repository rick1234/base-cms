<aside class="admin-sidebar">
    <a class="admin-sidebar__brand" href="{{ route('admin.dashboard') }}">{{ __('Base CMS') }}</a>
    <nav class="admin-navigation" aria-label="{{ __('Admin navigation') }}">
        <ul class="admin-navigation__list">
            <li class="admin-navigation__item"><a class="admin-navigation__link" href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="admin-navigation__item"><a class="admin-navigation__link" href="{{ route('wms.index') }}">{{ __('WMS Modules') }}</a></li>
            <li class="admin-navigation__item"><a class="admin-navigation__link" href="{{ route('admin.pages.index') }}">{{ __('Pages') }}</a></li>
        </ul>
    </nav>
</aside>
