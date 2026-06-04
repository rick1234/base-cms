<form class="search-widget" method="get" action="{{ route('frontend.search') }}" role="search">
    <div class="search-widget-inner">
        <label class="u-sr-only" for="site-search-query">{{ __('Search') }}</label>
        <input id="site-search-query" class="search-input" name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('Search') }}">
        <button class="search-button" type="submit" aria-label="{{ __('Search') }}">
            <span class="site-material-icon mso" aria-hidden="true">search</span>
        </button>
    </div>
</form>
