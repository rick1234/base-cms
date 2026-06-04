<div class="site-overlay-container" hidden data-site-menu-overlay></div>

<section class="offscreen-navigation-container" id="site-offscreen-navigation" aria-label="{{ __('Mobile navigation') }}" hidden data-site-menu-panel>
    <button class="btn-close-offscreen-navigation" type="button" data-site-menu-close>
        <span class="site-material-icon mso" aria-hidden="true">cancel</span>
        {{ __('Sluiten') }}
    </button>

    <div class="offscreen-navigation-inner-container active">
        <div class="offscreen-inner-widget">
            @include('frontend.partials.navigation')
        </div>

        @if (($enabledLocales ?? collect())->count() > 1)
            <div class="offscreen-inner-widget">
                @include('frontend.partials.language-switcher')
            </div>
        @endif

        @if (filter_var(data_get($domainTemplateSettings ?? [], 'search_enabled', false), FILTER_VALIDATE_BOOLEAN))
            <div class="offscreen-inner-widget">
                @include('frontend.partials.search-form')
            </div>
        @endif

        @if (count($activeDomain?->social_links ?? []) > 0)
            <div class="offscreen-inner-widget">
                @include('frontend.partials.social-links', ['links' => $activeDomain?->social_links ?? []])
            </div>
        @endif
    </div>
</section>
