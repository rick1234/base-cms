@props([
    'label' => null,
    'name' => config('cms_icons.fallback', 'extension'),
])

<span
    {{ $attributes->class('admin-material-icon') }}
    @if ($label)
        role="img"
        aria-label="{{ $label }}"
    @else
        aria-hidden="true"
    @endif
>{{ $name }}</span>
