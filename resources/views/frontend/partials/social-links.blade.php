@if (count($links ?? []) > 0)
    <nav class="site-socials socialmedia-widget" aria-label="{{ __('Social links') }}">
        @foreach ($links as $link)
            @php
                $label = $link['label'] ?? ucfirst($link['platform'] ?? __('Social'));
                $icon = $link['icon'] ?? strtoupper(substr((string) ($link['platform'] ?? $label), 0, 1));
            @endphp
            <a class="site-social-link socialmedia-icon" href="{{ $link['url'] }}" rel="noopener noreferrer" target="_blank" aria-label="{{ $label }}" title="{{ $label }}">
                <span aria-hidden="true">{{ $icon }}</span>
            </a>
        @endforeach
    </nav>
@endif
