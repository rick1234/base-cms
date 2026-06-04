@if (($enabledLocales ?? collect())->count() > 1)
    @php
        $activeLocale = $currentLocale ?? app()->getLocale();
        $currentLanguage = $enabledLocales->firstWhere('code', $activeLocale);
        $languageNativeName = static fn ($language): string => \Illuminate\Support\Str::ucfirst((string) ($language->native_name ?: $language->name ?: strtoupper($language->code)));
        $currentLanguageLabel = $currentLanguage ? $languageNativeName($currentLanguage) : strtoupper($activeLocale);
        $modalId = 'language-modal-'.str_replace(['-', '_'], '-', $activeLocale);
    @endphp

    <div class="language-widget" aria-label="{{ __('Language') }}" data-language-modal>
        <button
            class="current-language"
            type="button"
            title="{{ __('Change language') }}"
            aria-haspopup="dialog"
            aria-expanded="false"
            aria-controls="{{ $modalId }}"
            data-language-modal-trigger
        >
            <x-language-flag :locale="$activeLocale" :label="$currentLanguageLabel" decorative />
            <span class="u-sr-only">{{ __('Change language') }}: {{ $currentLanguageLabel }}</span>
        </button>

        <div class="language-modal" id="{{ $modalId }}" hidden data-language-modal-dialog>
            <div class="language-modal-backdrop" aria-hidden="true" data-language-modal-close></div>
            <section class="language-modal-panel" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
                <header class="language-modal-header">
                    <h2 class="language-modal-title" id="{{ $modalId }}-title">{{ __('Choose language') }}</h2>
                    <button class="language-modal-close" type="button" aria-label="{{ __('Close') }}" data-language-modal-close>
                        <span class="mso" aria-hidden="true">close</span>
                    </button>
                </header>

                <div class="language-modal-list">
                    @foreach ($enabledLocales as $language)
                        @php $languageLabel = $languageNativeName($language); @endphp
                        <form method="post" action="{{ route('locale.update', ['locale' => $language->code]) }}">
                            @csrf
                            <button
                                class="language-modal-option {{ $activeLocale === $language->code ? 'is-active' : '' }}"
                                type="submit"
                                @disabled($activeLocale === $language->code)
                            >
                                <x-language-flag :locale="$language->code" :label="$languageLabel" decorative />
                                <span class="language-modal-option-name">{{ $languageLabel }}</span>
                                @if ($activeLocale === $language->code)
                                    <span class="language-modal-option-status">{{ __('Current language') }}</span>
                                @endif
                            </button>
                        </form>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
@endif
