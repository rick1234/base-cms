@props([
    'name' => 'locale',
    'selected' => null,
    'locales' => ['nl', 'en', 'de', 'fr'],
    'idPrefix' => null,
])

@php
    $selectedLocale = strtolower((string) $selected);
    $defaultLabels = [
        'nl' => __('Dutch'),
        'en' => __('English'),
        'de' => __('German'),
        'fr' => __('French'),
    ];
    $options = collect($locales)
        ->map(function ($label, $key) use ($defaultLabels) {
            if (is_object($label)) {
                $code = (string) ($label->code ?? '');
                $optionLabel = method_exists($label, 'label') ? $label->label() : strtoupper($code);
            } elseif (is_array($label)) {
                $code = (string) ($label['code'] ?? $key);
                $optionLabel = (string) ($label['label'] ?? strtoupper($code));
            } elseif (is_string($key) && ! is_numeric($key)) {
                $code = (string) $key;
                $optionLabel = (string) $label;
            } else {
                $code = (string) $label;
                $optionLabel = strtoupper($code);
            }

            return [
                'code' => strtolower($code),
                'label' => $defaultLabels[strtolower($code)] ?? $optionLabel,
            ];
        })
        ->filter(fn (array $option): bool => $option['code'] !== '')
        ->unique('code')
        ->values();

    if ($selectedLocale !== '' && $options->where('code', $selectedLocale)->isEmpty()) {
        $options->push([
            'code' => $selectedLocale,
            'label' => strtoupper($selectedLocale),
        ]);
    }

    $inputIdPrefix = $idPrefix ?: str($name)->replace(['[', ']'], '_')->trim('_')->toString();
@endphp

<div {{ $attributes->class('language-choice-group')->merge(['role' => 'radiogroup']) }}>
    @foreach ($options as $option)
        <label class="language-choice {{ $selectedLocale === $option['code'] ? 'is-selected' : '' }}" title="{{ $option['label'] }}">
            <input
                id="{{ $inputIdPrefix }}_{{ $option['code'] }}"
                name="{{ $name }}"
                type="radio"
                value="{{ $option['code'] }}"
                @checked($selectedLocale === $option['code'])
            >
            <x-language-flag :locale="$option['code']" :label="$option['label']" decorative />
            <span class="u-sr-only">{{ $option['label'] }}</span>
        </label>
    @endforeach
</div>
