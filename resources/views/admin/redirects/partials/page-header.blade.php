<div class="page-header">
    <div class="page-header-title-container">
        <div class="page-header-title-image-container">
            <x-admin.material-icon class="is-large" name="route" />
        </div>
        <strong>{{ $section ?? $title }}</strong>
    </div>
</div>

<div class="breadcrumbs">
    <a href="{{ route(request()->routeIs('cms.*') ? 'cms.dashboard' : 'admin.dashboard') }}">{{ __('Home') }}</a>
    &rsaquo;
    <a href="{{ route(request()->routeIs('cms.*') ? 'cms.redirects.index' : 'admin.redirects.index') }}">{{ __('Redirects') }}</a>
    @isset($section)
        &rsaquo; {{ $section }}
    @endisset
</div>
