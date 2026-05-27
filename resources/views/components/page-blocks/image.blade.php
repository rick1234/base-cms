@php
    use App\Cms\PageBlocks\Support\PageBlockRenderer;

    $imageUrl = PageBlockRenderer::mediaUrl(data_get($data, 'image'));
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
                <img src="{{ $imageUrl }}" alt="{{ $alt }}">
            </a>
        @else
            <img src="{{ $imageUrl }}" alt="{{ $alt }}">
        @endif

        @if ($caption !== '')
            <figcaption>{{ $caption }}</figcaption>
        @endif
    </figure>
@endif
