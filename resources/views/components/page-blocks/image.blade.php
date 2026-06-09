@php
    use App\Cms\PageBlocks\Support\PageBlockRenderer;
    use App\Support\Media\HandledImage;

    $imagePath = data_get($data, 'image');
    $imageAlt = data_get($data, 'alt');
    $image = HandledImage::fromPath(is_string($imagePath) ? $imagePath : null, is_string($imageAlt) ? $imageAlt : null);
    $imageUrl = $image->handle(1200, null, false);
    $linkUrl = PageBlockRenderer::safeUrl(data_get($data, 'link_url'));
    $alt = (string) data_get($data, 'alt', '');
    $caption = (string) data_get($data, 'caption', '');
    $imageLayout = in_array(data_get($settings, 'layout'), ['default', 'wide', 'figure'], true) ? data_get($settings, 'layout') : 'default';
    $aspect = in_array(data_get($settings, 'aspect'), ['auto', '16-9', '4-3', '1-1'], true) ? data_get($settings, 'aspect') : 'auto';
@endphp

@if ($imageUrl)
    <figure class="page-block-image page-block-image--{{ $imageLayout }} page-block-image--aspect-{{ $aspect }}">
        @if ($linkUrl)
            <a href="{{ $linkUrl }}">
                <img src="{{ $imageUrl }}" alt="{{ $alt }}" loading="lazy" decoding="async">
            </a>
        @else
            <x-media.image :image="$image" :alt="$alt" :width="1200" :lightbox="true" group="page-block-images" />
        @endif

        @if ($caption !== '')
            <figcaption>{{ $caption }}</figcaption>
        @endif
    </figure>
@endif
