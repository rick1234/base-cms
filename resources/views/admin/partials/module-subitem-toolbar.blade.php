@php
    $activeScreenKey = $screenKey ?? ($module['handle'] ?? null);
    $dashboardModule = $activeScreenKey
        ? app(\App\Support\Admin\Dashboard\DashboardNavigationBuilder::class)
            ->moduleList(request()->routeIs('cms.*'))
            ->first(fn (array $candidate): bool => ($candidate['overview_screen'] ?? null) === $activeScreenKey
                || $candidate['subitems']->contains(fn (array $subitem): bool => ($subitem['screen_key'] ?? null) === $activeScreenKey))
        : null;
    $moduleLinks = $dashboardModule
        ? collect([[
            'screen_key' => $dashboardModule['overview_screen'],
            'title' => $dashboardModule['overview_title'],
            'url' => $dashboardModule['url'],
            'icon' => $dashboardModule['icon'],
        ]])->merge($dashboardModule['subitems'])
        : collect();
@endphp

@if ($moduleLinks->count() > 1)
    @foreach ($moduleLinks as $moduleLink)
        <a class="btn {{ ($moduleLink['screen_key'] ?? null) === $activeScreenKey ? 'is-active' : '' }}" href="{{ $moduleLink['url'] }}">
            <x-admin.material-icon :name="$moduleLink['icon']" />
            {{ __($moduleLink['title']) }}
        </a>
    @endforeach
@endif
