<div class="page-header">
    <div class="page-header-title-container">
        <div class="page-header-title-image-container">
            <x-admin.material-icon class="is-large" name="translate" />
        </div>
        <strong>{{ $section ?? $title }}</strong>
    </div>
</div>

<div class="breadcrumbs">
    @php($useCmsRoutes = $usesCmsRoutes ?? request()->routeIs('cms.*'))
    <a href="{{ route($useCmsRoutes ? 'cms.dashboard' : 'admin.dashboard') }}">{{ __('Home') }}</a>
    &rsaquo; <a href="{{ route($useCmsRoutes ? 'cms.translations.index' : 'admin.translations.index') }}">{{ __('Translations') }}</a>
    @isset($section)
        &rsaquo; {{ $section }}
    @endisset
</div>
