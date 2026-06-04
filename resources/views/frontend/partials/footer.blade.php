@php
    $settings = $domainTemplateSettings ?? [];
    $companyName = $activeDomain?->company_name ?: ($activeDomain?->siteTitle() ?? config('app.name', 'Base CMS'));
    $contactTitle = data_get($settings, 'footer_contact_title', __('Contactgegevens'));
    $contactText = data_get($settings, 'footer_contact_text');
    $contentTitle = data_get($settings, 'footer_content_title', __('Over ons'));
    $contentText = data_get($settings, 'footer_content_text', __('Een modern Laravel basisplatform voor maatwerk websites.'));
    $socialTitle = data_get($settings, 'footer_social_title', __('Social media'));
    $socialText = data_get($settings, 'footer_social_text', __('Volg ons op social media en blijf op de hoogte.'));
    $showCredit = filter_var(data_get($settings, 'show_footer_credit', false), FILTER_VALIDATE_BOOLEAN);
    $creditLabel = trim((string) data_get($settings, 'footer_credit_label', ''));
    $creditUrl = trim((string) data_get($settings, 'footer_credit_url', ''));
    $footerNavigationItems = collect($frontendFooterNavigationItems ?? []);
@endphp

<footer class="footer-container">
    <div class="wrapper-container">
        <div class="footer-partial">
            <section class="footer-widget" aria-labelledby="footer-contact-title">
                <h2 class="title" id="footer-contact-title">{{ $contactTitle }}</h2>
                <address class="footer-address">
                    <strong>{{ $companyName }}</strong>
                    @if ($contactText)
                        <span>{{ $contactText }}</span>
                    @elseif ($activeDomain?->host)
                        <span>{{ $activeDomain->host }}</span>
                    @endif
                </address>
            </section>
        </div>

        <div class="footer-partial">
            @if (($settings['social_placement'] ?? 'footer') === 'footer' && count($activeDomain?->social_links ?? []) > 0)
                <section class="footer-widget" aria-labelledby="footer-social-title">
                    <h2 class="title" id="footer-social-title">{{ $socialTitle }}</h2>
                    <p>{{ $socialText }}</p>
                    @include('frontend.partials.social-links', ['links' => $activeDomain?->social_links ?? []])
                </section>
            @endif

            @if (($settings['contact_form_placement'] ?? 'footer') === 'footer' && $activeDomain?->contactForm)
                <section class="footer-widget footer-contact-form" aria-label="{{ __('Contact') }}">
                    @include('frontend.forms.render', ['form' => $activeDomain->contactForm])
                </section>
            @endif
        </div>

        <div class="footer-partial">
            <section class="footer-widget" aria-labelledby="footer-content-title">
                <h2 class="title" id="footer-content-title">{{ $contentTitle }}</h2>
                <p>{{ $contentText }}</p>
            </section>
        </div>
    </div>
</footer>

<section class="site-info-container">
    <div class="wrapper-container">
        <div class="site-info-partial">
            <span>&copy; {{ now()->year }} {{ $companyName }}</span>
            @if ($footerNavigationItems->isNotEmpty())
                <nav class="site-info-navigation" aria-label="{{ __('Footer navigation') }}">
                    <ul class="site-info-links">
                        @foreach ($footerNavigationItems as $footerItem)
                            @php
                                $footerUrl = (string) ($footerItem['url'] ?? '#');
                            @endphp

                            <li>
                                <a href="{{ $footerUrl }}" @if ($footerItem['target_blank'] ?? false) target="_blank" rel="noopener" @endif>
                                    {{ $footerItem['title'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>

        @if ($showCredit && $creditLabel !== '')
            <div class="site-info-partial">
                <span>{{ __('Webdesign') }}:&nbsp;</span>
                @if ($creditUrl)
                    <a href="{{ $creditUrl }}" rel="noopener noreferrer" target="_blank">{{ $creditLabel }}</a>
                @else
                    <span>{{ $creditLabel }}</span>
                @endif
            </div>
        @endif
    </div>
</section>
