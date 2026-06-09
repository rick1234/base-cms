@props([
    'active' => 'identity',
    'domain',
    'steps' => [],
])

<ul class="tabmenu">
    @foreach ($steps as $tab => $step)
        <li class="tabmenu-item {{ $tab }}-button {{ $active === $tab ? 'active' : '' }}">
            @if ($domain->exists)
                <a href="{{ route('admin.domains.edit.step', ['domain' => $domain, 'step' => $tab]) }}">{{ __($step['label']) }}</a>
            @else
                {{ __($step['label']) }}
            @endif
        </li>
    @endforeach
</ul>
