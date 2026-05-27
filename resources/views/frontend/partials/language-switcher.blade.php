@if (($enabledLocales ?? collect())->count() > 1)
    <div class="language-widget" aria-label="{{ __('Language') }}">
        <span class="current-language">{{ strtoupper($currentLocale ?? app()->getLocale()) }}</span>
        <div class="language-listing">
            @foreach ($enabledLocales as $language)
                <form method="post" action="{{ route('locale.update', ['locale' => $language->code]) }}">
                    @csrf
                    <button class="language-listing-item {{ ($currentLocale ?? app()->getLocale()) === $language->code ? 'is-active' : '' }}" type="submit" @disabled(($currentLocale ?? app()->getLocale()) === $language->code)>
                        {{ strtoupper($language->code) }}
                    </button>
                </form>
            @endforeach
        </div>
    </div>
@endif
