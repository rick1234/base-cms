@props([
    'image',
    'width' => null,
    'height' => null,
    'crop' => false,
    'format' => null,
    'alt' => null,
    'class' => null,
    'link' => null,
    'lightbox' => true,
    'group' => 'content-images',
    'loading' => 'lazy',
])

@php
    $handledImage = $image instanceof \App\Support\Media\HandledImage
        ? $image
        : new \App\Support\Media\HandledImage($image, is_string($alt) ? $alt : null);
    $src = $handledImage->handle(
        is_numeric($width) ? (int) $width : null,
        is_numeric($height) ? (int) $height : null,
        filter_var($crop, FILTER_VALIDATE_BOOLEAN),
        is_string($format) ? $format : null,
    );
    $largeSrc = $link ?: ($lightbox ? $handledImage->lightbox(is_string($format) ? $format : null) : null);
    $resolvedAlt = is_string($alt) ? $alt : $handledImage->alt();
@endphp

@if ($src)
    @if ($largeSrc)
        <a
            href="{{ $largeSrc }}"
            @if ($lightbox && ! $link) data-fancybox="{{ $group }}" @endif
            @if ($resolvedAlt !== '') data-caption="{{ $resolvedAlt }}" @endif
        >
            <img
                @if ($class) class="{{ $class }}" @endif
                src="{{ $src }}"
                alt="{{ $resolvedAlt }}"
                @if (is_numeric($width)) width="{{ (int) $width }}" @endif
                @if (is_numeric($height)) height="{{ (int) $height }}" @endif
                loading="{{ $loading }}"
                decoding="async"
            >
        </a>
    @else
        <img
            @if ($class) class="{{ $class }}" @endif
            src="{{ $src }}"
            alt="{{ $resolvedAlt }}"
            @if (is_numeric($width)) width="{{ (int) $width }}" @endif
            @if (is_numeric($height)) height="{{ (int) $height }}" @endif
            loading="{{ $loading }}"
            decoding="async"
        >
    @endif
@endif
