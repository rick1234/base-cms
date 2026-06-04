@props([
    'label' => null,
    'name' => config('cms_icons.fallback', 'extension'),
])

<span
    {{ $attributes->class([
        'mso',
        'admin-delete-icon' => $name === 'delete',
    ]) }}
    @if ($label)
        role="img"
        aria-label="{{ $label }}"
    @else
        aria-hidden="true"
    @endif
>{{ $name }}</span>
