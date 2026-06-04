@php $widgetId = 'dashboard-widget-'.\Illuminate\Support\Str::slug($title); @endphp

<section class="dashboard-widget" aria-labelledby="{{ $widgetId }}">
    <header class="dashboard-widget-header">
        <h2 class="dashboard-widget-title" id="{{ $widgetId }}">{{ $title }}</h2>
    </header>

    <ul class="dashboard-recent-list">
        @forelse ($items as $item)
            <li class="dashboard-recent-item">
                <a class="dashboard-recent-link" href="{{ $item['url'] }}" title="{{ __('Open item') }}">
                    <span class="dashboard-recent-icon dashboard-module-icon-{{ $item['theme'] }}" aria-hidden="true">
                        <x-admin.material-icon :name="$item['icon']" />
                    </span>
                    <span class="dashboard-recent-content">
                        <strong>{{ $item['title'] }}</strong>
                        <span>{{ __($item['module']) }} &middot; {{ $item['occurred_at']->format('d-m-Y H:i') }}</span>
                    </span>
                </a>
            </li>
        @empty
            <li class="dashboard-widget-empty">{{ __('No recent items found.') }}</li>
        @endforelse
    </ul>
</section>
