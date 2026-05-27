@foreach ($items as $item)
    @php
        $url = (string) ($item['url'] ?? '#');
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $currentPath = '/'.trim(request()->path(), '/');
        $itemPath = '/'.trim($path, '/');
        $children = collect($item['children'] ?? []);
        $isActive = ! ($item['external'] ?? false) && $itemPath === $currentPath;
    @endphp

    <li @class(['navigation-list-item', 'has-children' => $children->isNotEmpty()])>
        <a
            @class(['navigation-link', 'is-active' => $isActive])
            href="{{ $url }}"
            @if ($children->isNotEmpty()) aria-haspopup="true" @endif
            @if ($item['target_blank'] ?? false) target="_blank" rel="noopener" @endif
        >
            {{ $item['title'] }}
        </a>

        @if ($children->isNotEmpty())
            <ul class="navigation-submenu-list">
                @include('frontend.partials.navigation.items', ['items' => $children])
            </ul>
        @endif
    </li>
@endforeach
