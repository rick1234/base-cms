@if (($isDomainPreviewMode ?? false) && ($domainPreviewDomains ?? collect())->isNotEmpty())
    <aside class="domain-dev-toolbar" aria-label="{{ __('Domain toolbar') }}">
        <div class="domain-dev-toolbar-meta">
            <span class="domain-dev-toolbar-badge">{{ __('Local') }}</span>
            <span class="domain-dev-toolbar-host">{{ $domainLocalHost ?? config('cms_domains.local_domain') }}</span>
            <span class="domain-dev-toolbar-current">
                {{ __('Viewing') }}:
                <strong>{{ $activeDomain?->host ?? __('shared fallback') }}</strong>
            </span>
        </div>

        <div class="domain-dev-toolbar-actions" role="group" aria-label="{{ __('Switch frontend domain') }}">
            @foreach ($domainPreviewDomains as $previewDomain)
                <form method="post" action="{{ route('frontend.domains.preview', $previewDomain) }}">
                    @csrf
                    <button class="domain-dev-toolbar-button {{ $activeDomain?->id === $previewDomain->id ? 'is-active' : '' }}" type="submit" @disabled($activeDomain?->id === $previewDomain->id)>
                        <span>{{ $previewDomain->host }}</span>
                        @if ($previewDomain->template)
                            <small>{{ $previewDomain->template->name }}</small>
                        @endif
                    </button>
                </form>
            @endforeach

            <form method="post" action="{{ route('frontend.domains.preview.clear') }}">
                @csrf
                @method('delete')
                <button class="domain-dev-toolbar-button" type="submit">{{ __('Clear') }}</button>
            </form>
        </div>
    </aside>
@endif
