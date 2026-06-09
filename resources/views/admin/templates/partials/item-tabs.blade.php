@php
    $tabs = [
        'identity' => __('Template'),
        'settings' => __('Settings'),
        'sections' => __('Defined sections'),
        'usp-sets' => __('USP sets'),
        'paths' => __('Paths'),
        'preview' => __('Preview'),
    ];
@endphp

<ul class="tabmenu">
    @foreach ($tabs as $tab => $label)
        <li class="tabmenu-item {{ $tab }}-button {{ $active === $tab ? 'active' : '' }}">
            @if ($template->exists)
                <a href="{{ $tab === 'identity' ? route('admin.templates.edit', $template) : route('admin.templates.edit.tab', ['websiteTemplate' => $template, 'tab' => $tab]) }}">{{ $label }}</a>
            @else
                {{ $label }}
            @endif
        </li>
    @endforeach
</ul>
