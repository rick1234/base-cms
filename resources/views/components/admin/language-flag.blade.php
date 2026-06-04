@props([
    'locale',
    'label' => null,
    'decorative' => false,
])

<x-language-flag :locale="$locale" :label="$label" :decorative="$decorative" {{ $attributes }} />
